<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Integration;

use Marktaborosi\StorageNavigator\Features;
use Marktaborosi\StorageNavigator\Limits;
use Marktaborosi\StorageNavigator\Options;
use Marktaborosi\StorageNavigator\Tests\Support\NavigatorTestCase;

final class DownloadTest extends NavigatorTestCase
{
    public function testDownload(): void
    {
        $response = $this->get($this->navigator(), ['action' => 'download', 'path' => 'docs/nested/deep.txt']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('deep', $this->body($response));
        self::assertSame('4', $response->getHeaderLine('Content-Length'));
        self::assertSame('bytes', $response->getHeaderLine('Accept-Ranges'));
        self::assertStringStartsWith('attachment; filename="deep.txt"', $response->getHeaderLine('Content-Disposition'));
    }

    public function testDirectoriesAndMissingFilesCannotBeDownloaded(): void
    {
        self::assertSame(404, $this->get($this->navigator(), ['action' => 'download', 'path' => 'docs'])->getStatusCode());
        self::assertSame(404, $this->get($this->navigator(), ['action' => 'download', 'path' => 'nope.txt'])->getStatusCode());
    }

    public function testRangeRequests(): void
    {
        $navigator = $this->navigator();

        $partial = $this->get($navigator, ['action' => 'raw', 'path' => 'media/clip.mp4'], ['Range' => 'bytes=10-19']);
        self::assertSame(206, $partial->getStatusCode());
        self::assertSame('bytes 10-19/1000', $partial->getHeaderLine('Content-Range'));
        self::assertSame('0123456789', $this->body($partial));

        $invalid = $this->get($navigator, ['action' => 'raw', 'path' => 'media/clip.mp4'], ['Range' => 'bytes=5000-']);
        self::assertSame(416, $invalid->getStatusCode());
    }

    public function testTextPreviewSizeLimit(): void
    {
        $this->filesystem->write('big.log', str_repeat('x', 2000));
        $navigator = $this->navigator(new Options(limits: new Limits(maxTextPreviewBytes: 1000)));

        self::assertSame(413, $this->get($navigator, ['action' => 'raw', 'path' => 'big.log'])->getStatusCode());
        self::assertSame(200, $this->get($navigator, ['action' => 'download', 'path' => 'big.log'])->getStatusCode());
    }

    public function testFolderAsZip(): void
    {
        $navigator = $this->navigator(new Options(features: Features::readOnly(zipDownload: true)));
        $response = $this->get($navigator, ['action' => 'zip', 'path' => 'docs']);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/zip', $response->getHeaderLine('Content-Type'));
        self::assertStringStartsWith('attachment; filename="docs.zip"', $response->getHeaderLine('Content-Disposition'));

        $file = tempnam(sys_get_temp_dir(), 'sn-zip');
        file_put_contents($file, $this->body($response));
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($file));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        self::assertSame('deep', $zip->getFromName('nested/deep.txt'));
        $zip->close();
        unlink($file);

        sort($names);
        self::assertSame(['logo.svg', 'nested/', 'nested/deep.txt', 'page.html', 'report.pdf'], $names);
    }

    public function testZipOfTheRootLeavesHiddenFilesOut(): void
    {
        $navigator = $this->navigator(new Options(features: Features::readOnly(zipDownload: true)));
        $body = $this->body($this->get($navigator, ['action' => 'zip']));

        self::assertStringNotContainsString('.env', $body);
        self::assertStringNotContainsString('.git/config', $body);
        self::assertStringContainsString('readme.md', $body);
    }

    public function testZipLimits(): void
    {
        $navigator = $this->navigator(new Options(features: Features::readOnly(zipDownload: true), limits: new Limits(maxZipEntries: 2)));

        self::assertSame(413, $this->get($navigator, ['action' => 'zip', 'path' => 'docs'])->getStatusCode());
    }
}
