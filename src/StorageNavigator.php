<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator;

use Marktaborosi\StorageNavigator\Action\ActionContext;
use Marktaborosi\StorageNavigator\Action\AssetAction;
use Marktaborosi\StorageNavigator\Action\CreateDirectoryAction;
use Marktaborosi\StorageNavigator\Action\DeleteAction;
use Marktaborosi\StorageNavigator\Action\DownloadAction;
use Marktaborosi\StorageNavigator\Action\ListAction;
use Marktaborosi\StorageNavigator\Action\RenameAction;
use Marktaborosi\StorageNavigator\Action\UploadAction;
use Marktaborosi\StorageNavigator\Action\ZipAction;
use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Entry\Listing;
use Marktaborosi\StorageNavigator\Exception\BadRequestException;
use Marktaborosi\StorageNavigator\Exception\ConfigurationException;
use Marktaborosi\StorageNavigator\Exception\HttpException;
use Marktaborosi\StorageNavigator\Exception\MethodNotAllowedException;
use Marktaborosi\StorageNavigator\Exception\MissingDependencyException;
use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Marktaborosi\StorageNavigator\Exception\PayloadTooLargeException;
use Marktaborosi\StorageNavigator\Exception\StorageException;
use Marktaborosi\StorageNavigator\Filter\Filter;
use Marktaborosi\StorageNavigator\Http\Action;
use Marktaborosi\StorageNavigator\Http\Responder;
use Marktaborosi\StorageNavigator\Http\SapiEmitter;
use Marktaborosi\StorageNavigator\Http\UrlGenerator;
use Marktaborosi\StorageNavigator\I18n\Formatter;
use Marktaborosi\StorageNavigator\I18n\Translator;
use Marktaborosi\StorageNavigator\Path\Path;
use Marktaborosi\StorageNavigator\Preview\PreviewResolver;
use Marktaborosi\StorageNavigator\Rendering\RendererInterface;
use Marktaborosi\StorageNavigator\Rendering\TwigRenderer;
use Marktaborosi\StorageNavigator\Security\AccessPolicy;
use Marktaborosi\StorageNavigator\Security\CsrfGuard;
use Marktaborosi\StorageNavigator\Storage\StorageInterface;
use Marktaborosi\StorageNavigator\Storage\WritableStorageInterface;
use Marktaborosi\StorageNavigator\View\ViewFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ZipStream\ZipStream;

/**
 * A file browser for one storage, as a PSR-15 request handler.
 *
 * Plain PHP:
 * <code>
 * (new StorageNavigator(Storage::local(__DIR__ . '/files')))->handleGlobals();
 * </code>
 *
 * Any PSR-15 / PSR-7 framework:
 * <code>
 * $response = $navigator->handle($request);
 * </code>
 */
final class StorageNavigator implements RequestHandlerInterface
{
    private readonly ActionContext $context;

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly Options $options = new Options(),
        ?ResponseFactoryInterface $responseFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?RendererInterface $renderer = null,
    ) {
        if ($options->features->hasWriteFeature() && !$storage instanceof WritableStorageInterface) {
            throw new ConfigurationException(sprintf('Write features need a writable storage; %s is read-only.', $storage::class));
        }
        if ($options->features->zipDownload && !class_exists(ZipStream::class)) {
            throw MissingDependencyException::forPackage('maennchen/zipstream-php', 'Features::$zipDownload');
        }

        $psr17 = new Psr17Factory();
        $translator = new Translator($options->locale, $options->translations);
        $formatter = new Formatter($options->dateFormat, $options->timezone, $translator->trans('number.decimal'));
        $assets = dirname(__DIR__) . '/resources/assets';
        $urls = new UrlGenerator($assets, $options->assetsUrl);
        $policy = new AccessPolicy($options);
        $previews = new PreviewResolver($options->limits->maxTextPreviewBytes);

        $this->context = new ActionContext(
            storage: $storage,
            options: $options,
            policy: $policy,
            responder: new Responder(
                $responseFactory ?? $psr17,
                $streamFactory ?? $psr17,
                $options->securityHeaders,
                self::origin($options->assetsUrl),
            ),
            renderer: $renderer ?? new TwigRenderer($options, $translator, $options->cacheDirectory),
            views: new ViewFactory($options, $policy, $urls, $translator, $formatter, $previews),
            urls: $urls,
            translator: $translator,
            previews: $previews,
            csrf: $options->csrfSecret === null ? null : new CsrfGuard($options->csrfSecret),
            assetsDirectory: $assets,
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $response = $this->dispatch($request);
        } catch (HttpException $e) {
            $response = $this->context->error($request, $e->statusCode(), $e->getMessage());
        } catch (StorageException $e) {
            $this->options->logger?->error('Storage Navigator: {message}', ['message' => $e->getMessage(), 'exception' => $e]);
            $response = $this->context->error($request, 500, $this->context->translator->trans('error.storage'));
        }

        if ($request->getMethod() === 'HEAD') {
            $response->getBody()->close();
            $response = $response->withBody((new Psr17Factory())->createStream(''));
        }

        return $response;
    }

    /**
     * Handles the current request from PHP's superglobals and sends the response.
     * For applications without a PSR-7 stack.
     */
    public function handleGlobals(): void
    {
        $factory = new Psr17Factory();
        $request = (new ServerRequestCreator($factory, $factory, $factory, $factory))->fromGlobals();

        try {
            $response = $this->handle($request);
        } catch (\Throwable $e) {
            $this->options->logger?->critical('Storage Navigator: {message}', ['message' => $e->getMessage(), 'exception' => $e]);
            $response = $factory->createResponse(500)
                ->withHeader('Content-Type', 'text/plain; charset=utf-8')
                ->withBody($factory->createStream('Internal Server Error'));
        }

        (new SapiEmitter())->emit($response);
    }

    /**
     * The visible contents of a directory, for use in your own code. Visibility rules
     * (hidden files, Options::$filter) apply; authorization does not, as there is no request.
     *
     * @throws NotFoundException
     */
    public function listing(string|Path $directory = ''): Listing
    {
        $path = $directory instanceof Path ? $directory : Path::fromString($directory);
        $entry = $this->storage->stat($path);
        if ($entry === null || !$entry->isDirectory() || !$this->context->policy->isReachable($entry)) {
            throw new NotFoundException('Directory not found.');
        }

        return (new Listing($path, $this->storage->list($path)))
            ->filter(Filter::callback(fn (Entry $child): bool => $this->context->policy->isVisible($child)))
            ->sorted();
    }

    private function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $action = $this->action($request);
        $method = $request->getMethod();

        if ($action->requiresPost()) {
            if ($method !== 'POST') {
                throw new MethodNotAllowedException('This action requires a POST request.');
            }
            if ($this->context->csrf === null) {
                throw new MethodNotAllowedException('Write operations are disabled.');
            }
            $this->context->csrf->validate($request);
        } elseif (!in_array($method, ['GET', 'HEAD'], true)) {
            $this->assertNotOversizedPost($request);
            throw new MethodNotAllowedException('Unsupported request method.');
        }

        return match ($action) {
            Action::List => (new ListAction())($request, $this->context),
            Action::Download => (new DownloadAction(inline: false))($request, $this->context),
            Action::Raw => (new DownloadAction(inline: true))($request, $this->context),
            Action::Zip => (new ZipAction())($request, $this->context),
            Action::Asset => (new AssetAction())($request, $this->context),
            Action::Upload => (new UploadAction())($request, $this->context),
            Action::CreateDirectory => (new CreateDirectoryAction())($request, $this->context),
            Action::Rename => (new RenameAction())($request, $this->context),
            Action::Delete => (new DeleteAction())($request, $this->context),
        };
    }

    private function action(ServerRequestInterface $request): Action
    {
        $value = $this->context->parameter($request, 'action') ?? Action::List->value;
        $action = Action::tryFrom($value);
        if ($action === null) {
            if ($request->getMethod() === 'POST') {
                $this->assertNotOversizedPost($request);
            }
            throw new BadRequestException('Unknown action.');
        }

        return $action;
    }

    /**
     * When a POST exceeds post_max_size, PHP drops the whole body. Report that as
     * "too large" instead of a confusing "unknown action".
     */
    private function assertNotOversizedPost(ServerRequestInterface $request): void
    {
        if ($request->getMethod() === 'POST'
            && (int) $request->getHeaderLine('Content-Length') > 0
            && empty($request->getParsedBody())
            && $request->getUploadedFiles() === []
        ) {
            throw new PayloadTooLargeException($this->context->translator->trans('upload.too_large_server'));
        }
    }

    private static function origin(?string $url): ?string
    {
        if ($url === null || preg_match('#^(https?:)?//[^/]+#i', $url, $match) !== 1) {
            return null;
        }

        return $match[0];
    }
}
