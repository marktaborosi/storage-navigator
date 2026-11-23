<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Action;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Exception\AccessDeniedException;
use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Marktaborosi\StorageNavigator\Http\Responder;
use Marktaborosi\StorageNavigator\Http\UrlGenerator;
use Marktaborosi\StorageNavigator\I18n\Translator;
use Marktaborosi\StorageNavigator\Options;
use Marktaborosi\StorageNavigator\Path\Path;
use Marktaborosi\StorageNavigator\Preview\PreviewResolver;
use Marktaborosi\StorageNavigator\Rendering\RendererInterface;
use Marktaborosi\StorageNavigator\Security\AccessPolicy;
use Marktaborosi\StorageNavigator\Security\CsrfGuard;
use Marktaborosi\StorageNavigator\Security\Operation;
use Marktaborosi\StorageNavigator\Storage\StorageInterface;
use Marktaborosi\StorageNavigator\Storage\WritableStorageInterface;
use Marktaborosi\StorageNavigator\View\ViewFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Services shared by the actions, and the checks every action performs.
 *
 * @internal
 */
final readonly class ActionContext
{
    public function __construct(
        public StorageInterface $storage,
        public Options $options,
        public AccessPolicy $policy,
        public Responder $responder,
        public RendererInterface $renderer,
        public ViewFactory $views,
        public UrlGenerator $urls,
        public Translator $translator,
        public PreviewResolver $previews,
        public ?CsrfGuard $csrf,
        public string $assetsDirectory,
    ) {
    }

    /**
     * A request parameter: the POST body wins over the query string.
     */
    public function parameter(ServerRequestInterface $request, string $name): ?string
    {
        $body = $request->getParsedBody();
        if (is_array($body) && is_string($body[$name] ?? null)) {
            return $body[$name];
        }
        $value = $request->getQueryParams()[$name] ?? null;

        return is_string($value) ? $value : null;
    }

    public function path(ServerRequestInterface $request): Path
    {
        return Path::fromString($this->parameter($request, 'path') ?? '');
    }

    /**
     * Resolves the entry at the request's path and checks that the client may see it
     * and perform the operation on it. Invisible entries are reported as missing, so
     * their existence is not revealed.
     *
     * @throws NotFoundException
     * @throws AccessDeniedException
     */
    public function authorize(ServerRequestInterface $request, Operation $operation, ?Path $path = null): Entry
    {
        $path ??= $this->path($request);
        $entry = $this->storage->stat($path);
        if ($entry === null || !$this->policy->isReachable($entry)) {
            throw new NotFoundException($this->translator->trans('error.not_found'));
        }
        if (!$this->policy->isAllowed($operation, $path, $request)) {
            throw new AccessDeniedException($this->translator->trans('error.forbidden'));
        }

        return $entry;
    }

    public function writableStorage(): WritableStorageInterface
    {
        \assert($this->storage instanceof WritableStorageInterface);

        return $this->storage;
    }

    public function wantsJson(ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeaderLine('Accept'), 'application/json')
            || $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Response of a successful write: JSON for the script, a redirect back to the
     * folder for plain form posts.
     */
    public function writeSuccess(ServerRequestInterface $request, Path $directory, string $message): ResponseInterface
    {
        if ($this->wantsJson($request)) {
            return $this->responder->json(['ok' => true, 'message' => $message]);
        }

        return $this->responder->redirect($this->urls->list($directory));
    }

    public function error(ServerRequestInterface $request, int $status, string $message): ResponseInterface
    {
        if ($this->wantsJson($request)) {
            return $this->responder->json(['ok' => false, 'error' => $message, 'status' => $status], $status);
        }

        $back = null;
        try {
            $path = $this->path($request);
            $back = $this->urls->list($path->isRoot() ? $path : $path->parent());
        } catch (\Throwable) {
            $back = $this->urls->list(Path::root());
        }

        return $this->responder->html($this->renderer->renderError($this->views->error($status, $message, $back, $request->getCookieParams())), $status);
    }
}
