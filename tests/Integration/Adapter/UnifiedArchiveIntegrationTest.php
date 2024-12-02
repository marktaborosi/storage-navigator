<?php

namespace Marktaborosi\Tests\Integration\Adapter;

use Marktaborosi\StorageBrowser\Adapters\UnifiedArchiveAdapter;
use Marktaborosi\StorageBrowser\Builders\FileStructureBuilder;
use Marktaborosi\StorageBrowser\Entities\DirectoryAttribute;
use Marktaborosi\StorageBrowser\Entities\FileAttribute;
use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\Tests\Traits\DummyFilesTrait;
use PHPUnit\Framework\TestCase;
use wapmorgan\UnifiedArchive\Exceptions\NonExistentArchiveFileException;

/**
 * Class ZipArchiveIntegrationTest
 *
 * Integration tests for the UnifiedArchiveAdapter using a real ZIP archive file.
 * Validates the behavior of the adapter methods for retrieving file structures
 * and checking file/directory existence.
 *
 * @package Marktaborosi\Tests\Integration\Adapter
 */
class UnifiedArchiveIntegrationTest extends TestCase
{
    use DummyFilesTrait;

    /**
     * Path to the example ZIP archive used for testing.
     */
    const EXAMPLE_FILE_PATH = self::TEST_FILES_STORAGE_LOCATION . "archives" . DIRECTORY_SEPARATOR . "example.zip";

    /**
     * Tests that `getFileStructure` returns the correct structure from the UnifiedArchive.
     *
     * Validates that the adapter correctly retrieves files and directories
     * from the provided archive and matches the expected structure.
     *
     * @throws NonExistentArchiveFileException
     */
    public function test_get_file_structure_returns_correct_structure_from_unified_archive(): void
    {
        // Expected structures based on the created files and directories
        $expectedFile = new FileAttribute(
            directoryPath: "",
            filename: "test-file.txt",
            extension: "txt",
            byteSize: 4194370,
            lastModified: 1696448326
        );

        $expectedDirectory = new DirectoryAttribute(
            name: "test-dir",
            path: "",
            lastModified: 1724691478
        );

        // Build the expected file structure
        $fileStructureBuilder = new FileStructureBuilder();
        $fileStructureBuilder->addFile($expectedFile);
        $fileStructureBuilder->addDirectory($expectedDirectory);
        $expectedFileStructure = $fileStructureBuilder->sortBy()->build();

        // Test the actual adapter with the real filesystem
        $adapter = new UnifiedArchiveAdapter(self::EXAMPLE_FILE_PATH);
        $structure = $adapter->getFileStructure("/");

        // Assertions
        $this->assertInstanceOf(FileStructure::class, $structure);

        $this->assertCount(1, $structure->getDirectories());
        $this->assertInstanceOf(DirectoryAttribute::class, $structure->getDirectories()[0]);

        $this->assertCount(1, $structure->getFiles());
        $this->assertInstanceOf(FileAttribute::class, $structure->getFiles()[1]);

        $this->assertEquals($expectedFileStructure, $structure);
    }

    /**
     * Tests that `fileOrDirectoryExists` correctly identifies the existence of a real file in the archive.
     *
     * This test verifies that the adapter returns true for a file that exists in the archive.
     */
    public function test_file_exists_with_real_file(): void
    {
        $adapter = new UnifiedArchiveAdapter(self::EXAMPLE_FILE_PATH);

        $exists = $adapter->fileOrDirectoryExists("test-file.txt");

        $this->assertTrue($exists);
    }

    /**
     * Tests that `fileOrDirectoryExists` correctly identifies the existence of a nested file in the archive.
     *
     * This test verifies that the adapter returns true for a file located within a directory in the archive.
     */
    public function test_file_exists_with_real_nested_file(): void
    {
        $adapter = new UnifiedArchiveAdapter(self::EXAMPLE_FILE_PATH);

        $exists = $adapter->fileOrDirectoryExists("test-dir/empty.txt");

        $this->assertTrue($exists);
    }

    /**
     * Tests that `fileOrDirectoryExists` returns true for directories.
     *
     */
    public function test_directory_exists_with_real_directory(): void
    {
        $adapter = new UnifiedArchiveAdapter(self::EXAMPLE_FILE_PATH);

        $exists = $adapter->fileOrDirectoryExists("test-dir");

        $this->assertTrue($exists);
    }
}
