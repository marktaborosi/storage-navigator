<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Integration;

use Marktaborosi\StorageNavigator\Filter\Filter;
use Marktaborosi\StorageNavigator\Limits;
use Marktaborosi\StorageNavigator\Security\Operation;
use Marktaborosi\StorageNavigator\Tests\Support\NavigatorTestCase;

final class WriteOperationsTest extends NavigatorTestCase
{
    public function testUpload(): void
    {
        $response = $this->post($this->navigator($this->writableOptions()), ['action' => 'upload', 'path' => 'docs'], [
            'files' => [$this->upload('new.txt', 'hello'), $this->upload('photo.jpg', 'jpg')],
        ]);

        self::assertSame(200, $response->getStatusCode(), $this->body($response));
        self::assertTrue($this->json($response)['ok']);
        self::assertSame('hello', $this->filesystem->read('docs/new.txt'));
        self::assertTrue($this->filesystem->fileExists('docs/photo.jpg'));
    }

    public function testUploadWithoutJavaScriptRedirectsBack(): void
    {
        $response = $this->post($this->navigator($this->writableOptions()), ['action' => 'upload', 'path' => 'docs'], [
            'files' => [$this->upload('plain.txt', 'x')],
        ], json: false);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('?path=docs', $response->getHeaderLine('Location'));
    }

    public function testUploadUsesOnlyTheBaseName(): void
    {
        $this->post($this->navigator($this->writableOptions()), ['action' => 'upload', 'path' => 'docs'], [
            'files' => [$this->upload('../../escape.txt', 'x')],
        ]);

        self::assertTrue($this->filesystem->fileExists('docs/escape.txt'));
        self::assertFalse($this->filesystem->fileExists('escape.txt'));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function rejectedUploads(): iterable
    {
        yield 'php' => ['shell.php', 400];
        yield 'double extension' => ['shell.php.jpg', 400];
        yield 'htaccess' => ['.htaccess', 400];
        yield 'dotfile' => ['.env', 400];
        yield 'existing file' => ['readme.md', 409];
        yield 'existing folder' => ['docs', 409];
        yield 'trailing dot' => ['name.', 400];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rejectedUploads')]
    public function testRejectedUploads(string $name, int $status): void
    {
        $before = $this->filesystem->fileExists($name) ? $this->filesystem->read($name) : null;
        $response = $this->post($this->navigator($this->writableOptions()), ['action' => 'upload', 'path' => ''], [
            'files' => [$this->upload($name, 'payload')],
        ]);

        self::assertSame($status, $response->getStatusCode(), $this->body($response));
        if ($before !== null) {
            self::assertSame($before, $this->filesystem->read($name));
        }
    }

    public function testOneBadFileRejectsTheWholeUpload(): void
    {
        $this->post($this->navigator($this->writableOptions()), ['action' => 'upload', 'path' => ''], [
            'files' => [$this->upload('good.txt', 'ok'), $this->upload('bad.phtml', 'x')],
        ]);

        self::assertFalse($this->filesystem->fileExists('good.txt'));
    }

    public function testUploadLimits(): void
    {
        $navigator = $this->navigator($this->writableOptions(limits: new Limits(maxUploadBytes: 3)));

        self::assertSame(413, $this->post($navigator, ['action' => 'upload', 'path' => ''], ['files' => [$this->upload('a.txt', 'four')]])->getStatusCode());
        self::assertSame(413, $this->post($navigator, ['action' => 'upload', 'path' => ''], ['files' => [$this->upload('a.txt', '', UPLOAD_ERR_INI_SIZE)]])->getStatusCode());
        self::assertSame(400, $this->post($navigator, ['action' => 'upload', 'path' => ''])->getStatusCode());
    }

    public function testOverwriteWhenEnabled(): void
    {
        $navigator = $this->navigator($this->writableOptions(overwriteUploads: true));
        $this->post($navigator, ['action' => 'upload', 'path' => ''], ['files' => [$this->upload('notes.txt', 'replaced')]]);

        self::assertSame('replaced', $this->filesystem->read('notes.txt'));
    }

    public function testCsrfIsRequired(): void
    {
        $navigator = $this->navigator($this->writableOptions());

        $response = $this->post($navigator, ['action' => 'delete', 'path' => 'notes.txt'], token: 'forged');
        self::assertSame(403, $response->getStatusCode());
        self::assertTrue($this->filesystem->fileExists('notes.txt'));
    }

    public function testCreateDirectory(): void
    {
        $navigator = $this->navigator($this->writableOptions());

        self::assertSame(200, $this->post($navigator, ['action' => 'mkdir', 'path' => 'docs', 'name' => 'Reports 2027'])->getStatusCode());
        self::assertTrue($this->filesystem->directoryExists('docs/Reports 2027'));
        self::assertSame(409, $this->post($navigator, ['action' => 'mkdir', 'path' => 'docs', 'name' => 'Reports 2027'])->getStatusCode());
        self::assertSame(400, $this->post($navigator, ['action' => 'mkdir', 'path' => 'docs', 'name' => '../up'])->getStatusCode());
        self::assertSame(400, $this->post($navigator, ['action' => 'mkdir', 'path' => '', 'name' => '.hidden'])->getStatusCode());
    }

    public function testRename(): void
    {
        $navigator = $this->navigator($this->writableOptions());

        self::assertSame(200, $this->post($navigator, ['action' => 'rename', 'path' => 'notes.txt', 'name' => 'todo.txt'])->getStatusCode());
        self::assertTrue($this->filesystem->fileExists('todo.txt'));
        self::assertSame(409, $this->post($navigator, ['action' => 'rename', 'path' => 'todo.txt', 'name' => 'readme.md'])->getStatusCode());
        self::assertSame(400, $this->post($navigator, ['action' => 'rename', 'path' => 'todo.txt', 'name' => 'todo.php'])->getStatusCode());
        self::assertSame(400, $this->post($navigator, ['action' => 'rename', 'path' => 'todo.txt', 'name' => 'a/b'])->getStatusCode());
        self::assertSame(400, $this->post($navigator, ['action' => 'rename', 'path' => '', 'name' => 'x'])->getStatusCode());
        self::assertSame(200, $this->post($navigator, ['action' => 'rename', 'path' => 'docs', 'name' => 'documents'])->getStatusCode());
        self::assertTrue($this->filesystem->fileExists('documents/nested/deep.txt'));
    }

    public function testDelete(): void
    {
        $navigator = $this->navigator($this->writableOptions());

        self::assertSame(200, $this->post($navigator, ['action' => 'delete', 'path' => 'notes.txt'])->getStatusCode());
        self::assertFalse($this->filesystem->fileExists('notes.txt'));
        self::assertSame(200, $this->post($navigator, ['action' => 'delete', 'path' => 'docs'])->getStatusCode());
        self::assertFalse($this->filesystem->directoryExists('docs'));
        self::assertSame(400, $this->post($navigator, ['action' => 'delete', 'path' => ''])->getStatusCode());
        self::assertSame(404, $this->post($navigator, ['action' => 'delete', 'path' => '.env'])->getStatusCode());
    }

    public function testFoldersWithProtectedContentAreNotDeleted(): void
    {
        $this->filesystem->write('docs/.htaccess', 'deny from all');
        $navigator = $this->navigator($this->writableOptions());

        self::assertSame(409, $this->post($navigator, ['action' => 'delete', 'path' => 'docs'])->getStatusCode());
        self::assertTrue($this->filesystem->fileExists('docs/.htaccess'));
    }

    public function testFilteredEntriesCannotBeCreatedOrTouched(): void
    {
        $navigator = $this->navigator($this->writableOptions(filter: Filter::not(Filter::name('*.log', 'private'))));

        self::assertSame(400, $this->post($navigator, ['action' => 'upload', 'path' => ''], ['files' => [$this->upload('debug.log', 'x')]])->getStatusCode());
        self::assertSame(400, $this->post($navigator, ['action' => 'rename', 'path' => 'notes.txt', 'name' => 'notes.log'])->getStatusCode());
        self::assertSame(404, $this->post($navigator, ['action' => 'delete', 'path' => 'private/secret.txt'])->getStatusCode());
    }

    public function testAuthorizeCallbackCanForbidWrites(): void
    {
        $navigator = $this->navigator($this->writableOptions(
            authorize: static fn (Operation $operation): bool => !$operation->isWrite(),
        ));

        self::assertSame(403, $this->post($navigator, ['action' => 'delete', 'path' => 'notes.txt'])->getStatusCode());
        self::assertTrue($this->filesystem->fileExists('notes.txt'));
        self::assertStringNotContainsString('data-can-delete', $this->body($this->get($navigator)));
    }

    public function testPageExposesWriteControlsOnlyWhenAllowed(): void
    {
        $html = $this->body($this->get($this->navigator($this->writableOptions())));

        self::assertStringContainsString('data-sn-action="upload"', $html);
        self::assertStringContainsString('name="_token"', $html);
        self::assertStringNotContainsString('data-sn-action="upload"', $this->body($this->get($this->navigator())));
    }

    public function testOversizedPostIsReportedAsTooLarge(): void
    {
        $request = $this->request('POST', '/files')->withHeader('Content-Length', '999999999');

        self::assertSame(413, $this->navigator($this->writableOptions())->handle($request)->getStatusCode());
    }
}
