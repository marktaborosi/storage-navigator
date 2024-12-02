<?php

namespace Marktaborosi\Tests\Unit\Structure;

use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\Entities\FileAttribute;
use Marktaborosi\StorageBrowser\Entities\DirectoryAttribute;
use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\StorageBrowser\Structure\FileStructureFilterer;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class FileStructureFiltererTest
 *
 * This class tests the `FileStructureFilterer` to ensure it correctly filters files and directories
 * based on ignore criteria such as file extensions and filenames defined in the `FileBrowserConfig`.
 *
 * @package Marktaborosi\Tests\Unit\Structure
 */
class FileStructureFiltererTest extends TestCase
{
    /**
     * Mocked instance of the `FileBrowserConfig`.
     *
     * @var FileBrowserConfig|MockObject
     */
    private FileBrowserConfig|MockObject $fileBrowserConfigMock;

    /**
     * Sets up the test environment.
     *
     * Initializes a mocked instance of `FileBrowserConfig` for use in the tests.
     *
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->fileBrowserConfigMock = $this->createMock(FileBrowserConfig::class);
    }

    /**
     * Tests that files with extensions in the ignore list are filtered out.
     *
     * Verifies that files with extensions like `txt` and `log` are excluded from
     * the `FileStructure`, while other files remain unaffected.
     *
     * @throws Exception
     */
    public function test_filter_ignores_files_with_ignored_extensions(): void
    {
        $this->fileBrowserConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['ignore_extensions', null, ['txt', 'log']],
                ['ignore_filenames', null, []],
            ]);

        $file1 = new FileAttribute('', 'document.txt', 'txt', 100, time());
        $file2 = new FileAttribute('', 'image.jpg', 'jpg', 200, time());
        $file3 = new FileAttribute('', 'logfile.log', 'log', 300, time());
        $fileStructure = new FileStructure([$file1, $file2, $file3]);

        $filteredStructure = FileStructureFilterer::filter($fileStructure, $this->fileBrowserConfigMock);

        $this->assertCount(1, $filteredStructure->getEntries());
        $this->assertEquals('image.jpg', $filteredStructure->getEntries()[0]->getFilename());
    }

    /**
     * Tests that files with filenames in the ignore list are filtered out.
     *
     * Verifies that specific filenames like `image.jpg` and `logfile.log` are excluded
     * from the `FileStructure`, while other files remain.
     */
    public function test_filter_ignores_files_with_ignored_filenames(): void
    {
        $this->fileBrowserConfigMock->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['ignore_extensions', null, []],
                ['ignore_filenames', null, ['image.jpg', 'logfile.log']],
            ]);

        $file1 = new FileAttribute('', 'document.txt', 'txt', 100, time());
        $file2 = new FileAttribute('', 'image.jpg', 'jpg', 200, time());
        $file3 = new FileAttribute('', 'logfile.log', 'log', 300, time());
        $fileStructure = new FileStructure([$file1, $file2, $file3]);

        $filteredStructure = FileStructureFilterer::filter($fileStructure, $this->fileBrowserConfigMock);

        $this->assertCount(1, $filteredStructure->getEntries());
        $this->assertEquals('document.txt', $filteredStructure->getEntries()[0]->getFilename());
    }

    /**
     * Tests that directories are not filtered out.
     *
     * Verifies that filtering is applied only to files and that directories remain unaffected
     * in the `FileStructure`.
     */
    public function test_filter_ignores_only_files_not_directories(): void
    {
        $this->fileBrowserConfigMock->method('get')
            ->willReturnMap([
                ['ignore_extensions', null, ['txt']],
                ['ignore_filenames', null, []],
            ]);

        $file1 = new FileAttribute('', 'document.txt', 'txt', 100, time());
        $directory = new DirectoryAttribute('folder', '', time());
        $fileStructure = new FileStructure([$file1, $directory]);

        $filteredStructure = FileStructureFilterer::filter($fileStructure, $this->fileBrowserConfigMock);

        $this->assertCount(1, $filteredStructure->getEntries());
        $this->assertInstanceOf(DirectoryAttribute::class, $filteredStructure->getEntries()[0]);
    }

    /**
     * Tests that no filtering occurs when the ignore lists are empty.
     *
     * Verifies that all files and directories remain in the `FileStructure` if no ignore criteria are defined.
     */
    public function test_filter_with_empty_ignore_lists(): void
    {
        $this->fileBrowserConfigMock->method('get')
            ->willReturnMap([
                ['ignore_extensions', null, []],
                ['ignore_filenames', null, []],
            ]);

        $file1 = new FileAttribute('', 'document', 'txt', 100, time());
        $file2 = new FileAttribute('', 'image', 'jpg', 200, time());
        $file3 = new FileAttribute('', 'logfile', 'log', 300, time());
        $fileStructure = new FileStructure([$file1, $file2, $file3]);

        $filteredStructure = FileStructureFilterer::filter($fileStructure, $this->fileBrowserConfigMock);

        $this->assertCount(3, $filteredStructure->getEntries());
    }

    /**
     * Tests that multiple ignore criteria work together to filter files.
     *
     * Verifies that files matching multiple ignore criteria (e.g., extensions and filenames)
     * are excluded, while other files remain unaffected.
     */
    public function test_filter_with_multiple_ignore_criteria(): void
    {
        $fileBrowserConfig = new FileBrowserConfig([
            'ignore_extensions' => ['txt', 'jpg'],
            'ignore_filenames' => ['logfile.log'],
        ]);

        $file1 = new FileAttribute('', 'document', 'txt', 100, time());
        $file2 = new FileAttribute('', 'image', 'jpg', 200, time());
        $file3 = new FileAttribute('', 'logfile.log', 'log', 300, time());
        $file4 = new FileAttribute('', 'other_file', 'doc', 400, time());
        $fileStructure = new FileStructure([$file1, $file2, $file3, $file4]);

        $filteredStructure = FileStructureFilterer::filter($fileStructure, $fileBrowserConfig);

        $this->assertCount(1, $filteredStructure->getEntries());
        $this->assertEquals('other_file', $filteredStructure->getEntries()[0]->getFilename());
    }
}
