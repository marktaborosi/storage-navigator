<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Support;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Marktaborosi\StorageNavigator\Features;
use Marktaborosi\StorageNavigator\Options;
use Marktaborosi\StorageNavigator\Security\CsrfGuard;
use Marktaborosi\StorageNavigator\Storage\FlysystemStorage;
use Marktaborosi\StorageNavigator\Storage\StorageInterface;
use Marktaborosi\StorageNavigator\StorageNavigator;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\UploadedFile;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class NavigatorTestCase extends TestCase
{
    protected const string SECRET = 'test-secret-0123456789abcdef0123456789';

    protected Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem(new InMemoryFilesystemAdapter());
        $this->filesystem->write('readme.md', "# Hello\n");
        $this->filesystem->write('notes.txt', 'Some notes');
        $this->filesystem->write('.env', 'SECRET=1');
        $this->filesystem->write('docs/report.pdf', '%PDF-1.4 fake');
        $this->filesystem->write('docs/page.html', '<script>alert(1)</script>');
        $this->filesystem->write('docs/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->filesystem->write('docs/nested/deep.txt', 'deep');
        $this->filesystem->write('private/secret.txt', 'top secret');
        $this->filesystem->write('.git/config', '[core]');
        $this->filesystem->write('media/clip.mp4', str_repeat('0123456789', 100));
    }

    protected function storage(): StorageInterface
    {
        return new FlysystemStorage($this->filesystem);
    }

    protected function navigator(?Options $options = null, ?StorageInterface $storage = null): StorageNavigator
    {
        return new StorageNavigator($storage ?? $this->storage(), $options ?? new Options());
    }

    protected function writableOptions(mixed ...$overrides): Options
    {
        return new Options(...([
            'features' => Features::all(),
            'csrfSecret' => self::SECRET,
        ] + $overrides));
    }

    /**
     * @param array<string, string> $query
     */
    protected function get(StorageNavigator $navigator, array $query = [], array $headers = []): ResponseInterface
    {
        $request = (new ServerRequest('GET', '/files?' . http_build_query($query), $headers))->withQueryParams($query);

        return $navigator->handle($request);
    }

    /**
     * A POST with a valid CSRF token, unless $token is given.
     *
     * @param array<string, string>                    $body
     * @param array<string, list<UploadedFile>> $files
     */
    protected function post(StorageNavigator $navigator, array $body, array $files = [], ?string $token = null, bool $json = true): ResponseInterface
    {
        $valid = $this->csrfToken();
        $request = (new ServerRequest('POST', '/files', $json ? ['Accept' => 'application/json'] : []))
            ->withParsedBody($body + ['_token' => $token ?? $valid])
            ->withCookieParams([CsrfGuard::COOKIE => $valid])
            ->withUploadedFiles($files);

        return $navigator->handle($request);
    }

    protected function csrfToken(): string
    {
        static $token = null;
        if ($token === null) {
            $token = (new CsrfGuard(self::SECRET))->issue(new ServerRequest('GET', 'https://example.com/'))['token'];
        }

        return $token;
    }

    protected function upload(string $name, string $content, int $error = UPLOAD_ERR_OK): UploadedFile
    {
        $stream = fopen('php://temp', 'w+b');
        \assert($stream !== false);
        fwrite($stream, $content);
        rewind($stream);

        return new UploadedFile($stream, strlen($content), $error, $name, 'application/octet-stream');
    }

    protected function body(ResponseInterface $response): string
    {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return $body->getContents();
    }

    /**
     * @return array<string, mixed>
     */
    protected function json(ResponseInterface $response): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($this->body($response), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    protected function request(string $method, string $uri): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }
}
