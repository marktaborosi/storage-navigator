<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Integration;

use Marktaborosi\StorageNavigator\Exception\ConfigurationException;
use Marktaborosi\StorageNavigator\Features;
use Marktaborosi\StorageNavigator\Options;
use Marktaborosi\StorageNavigator\Storage\ArchiveStorage;
use Marktaborosi\StorageNavigator\Storage\Storage;
use Marktaborosi\StorageNavigator\StorageNavigator;
use Marktaborosi\StorageNavigator\Tests\Support\NavigatorTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Regression tests for the 2.x audit findings and other attacks.
 */
final class SecurityTest extends NavigatorTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/sn-security-' . bin2hex(random_bytes(4));
        mkdir($this->directory . '/root/public', 0777, true);
        file_put_contents($this->directory . '/root/public/ok.txt', 'ok');
        file_put_contents($this->directory . '/outside.txt', 'OUTSIDE-SECRET');
        file_put_contents($this->directory . '/root/.env', 'APP_KEY=secret');
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
        }
        rmdir($this->directory);
    }

    private function local(?Options $options = null): StorageNavigator
    {
        return new StorageNavigator(Storage::local($this->directory . '/root'), $options ?? new Options());
    }

    #[DataProvider('traversalAttempts')]
    public function testCannotEscapeTheRoot(string $action, string $path): void
    {
        $response = $this->get($this->local(), ['action' => $action, 'path' => $path]);

        self::assertContains($response->getStatusCode(), [400, 404]);
        self::assertStringNotContainsString('OUTSIDE-SECRET', $this->body($response));
        self::assertStringNotContainsString('root:x:', $this->body($response));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function traversalAttempts(): iterable
    {
        foreach (['download', 'raw', 'list'] as $action) {
            yield "$action parent" => [$action, '../outside.txt'];
            yield "$action nested parent" => [$action, 'public/../../outside.txt'];
            yield "$action backslashes" => [$action, '..\\outside.txt'];
            yield "$action absolute" => [$action, '/etc/passwd'];
            yield "$action absolute dir" => [$action, $action === 'list' ? '/etc' : '/etc/passwd'];
            yield "$action nul byte" => [$action, "public/ok.txt\0.jpg"];
        }
    }

    public function testDotfilesCannotBeDownloadedEvenWhenAskedForDirectly(): void
    {
        self::assertSame(404, $this->get($this->local(), ['action' => 'download', 'path' => '.env'])->getStatusCode());
        self::assertSame(404, $this->get($this->navigator(), ['action' => 'download', 'path' => '.git/config'])->getStatusCode());
    }

    public function testSymlinksCannotLeadOutside(): void
    {
        if (!function_exists('symlink') || !@symlink($this->directory . '/outside.txt', $this->directory . '/root/public/link.txt')) {
            self::markTestSkipped('Symbolic links are not available.');
        }
        symlink($this->directory, $this->directory . '/root/public/up');
        $navigator = $this->local();

        self::assertStringNotContainsString('link.txt', $this->body($this->get($navigator, ['path' => 'public'])));
        self::assertSame(404, $this->get($navigator, ['action' => 'download', 'path' => 'public/link.txt'])->getStatusCode());
        self::assertSame(404, $this->get($navigator, ['action' => 'download', 'path' => 'public/up/outside.txt'])->getStatusCode());
    }

    public function testDisabledFeaturesAreEnforcedOnTheServer(): void
    {
        // Regression: in 2.x disableFileDownload only hid the buttons.
        $navigator = $this->navigator(new Options(features: new Features(download: false, preview: false)));

        self::assertSame(403, $this->get($navigator, ['action' => 'download', 'path' => 'notes.txt'])->getStatusCode());
        self::assertSame(403, $this->get($navigator, ['action' => 'raw', 'path' => 'notes.txt'])->getStatusCode());
        self::assertSame(403, $this->get($navigator, ['action' => 'zip', 'path' => 'docs'])->getStatusCode());
        self::assertStringNotContainsString('action=download', $this->body($this->get($navigator)));
    }

    public function testWritesAreImpossibleWithoutTheFeature(): void
    {
        $navigator = $this->navigator();

        self::assertSame(405, $this->post($navigator, ['action' => 'delete', 'path' => 'notes.txt'])->getStatusCode());
        self::assertTrue($this->filesystem->fileExists('notes.txt'));
    }

    public function testPreviewNeverRendersActiveContent(): void
    {
        $navigator = $this->navigator();

        $html = $this->get($navigator, ['action' => 'raw', 'path' => 'docs/page.html']);
        self::assertSame('text/plain; charset=utf-8', $html->getHeaderLine('Content-Type'));
        self::assertStringContainsString('sandbox', $html->getHeaderLine('Content-Security-Policy'));
        self::assertSame('nosniff', $html->getHeaderLine('X-Content-Type-Options'));

        $svg = $this->get($navigator, ['action' => 'raw', 'path' => 'docs/logo.svg']);
        self::assertSame('image/svg+xml', $svg->getHeaderLine('Content-Type'));
        self::assertStringContainsString('sandbox', $svg->getHeaderLine('Content-Security-Policy'));

        $pdf = $this->get($navigator, ['action' => 'raw', 'path' => 'docs/report.pdf']);
        self::assertSame('application/pdf', $pdf->getHeaderLine('Content-Type'));
        self::assertStringStartsWith('inline;', $pdf->getHeaderLine('Content-Disposition'));
    }

    public function testFilesWithoutPreviewAreNotServedInline(): void
    {
        $this->filesystem->write('app.exe', 'MZ');

        self::assertSame(404, $this->get($this->navigator(), ['action' => 'raw', 'path' => 'app.exe'])->getStatusCode());
    }

    public function testThemeNamesCannotReadFiles(): void
    {
        // Regression: in 2.x any readable file could be passed as themePath.
        $this->expectException(ConfigurationException::class);
        new Options(theme: $this->directory . '/outside.txt');
    }

    public function testReadOnlyStorageRejectsWriteFeatures(): void
    {
        $this->expectException(ConfigurationException::class);
        new StorageNavigator(new ArchiveStorage(__FILE__), new Options(features: new Features(upload: true), csrfSecret: self::SECRET));
    }

    public function testArchiveEntriesWithUnsafeNamesAreIgnored(): void
    {
        $zip = new \ZipArchive();
        $file = $this->directory . '/evil.zip';
        $zip->open($file, \ZipArchive::CREATE);
        $zip->addFromString('../../escape.txt', 'escape');
        $zip->addFromString('safe/inner.txt', 'inner');
        $zip->close();

        $navigator = new StorageNavigator(Storage::archive($file));
        $listing = $navigator->listing();

        self::assertSame(['safe'], array_map(static fn ($e) => $e->name(), $listing->entries));
        self::assertSame('inner', $this->body($this->get($navigator, ['action' => 'download', 'path' => 'safe/inner.txt'])));
    }
}
