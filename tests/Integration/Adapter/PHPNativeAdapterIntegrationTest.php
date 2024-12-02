<?php

namespace Marktaborosi\Tests\Integration\Adapter;

use Marktaborosi\StorageBrowser\Adapters\PHPNativeAdapter;
use Marktaborosi\StorageBrowser\Builders\FileStructureBuilder;
use Marktaborosi\StorageBrowser\Entities\DirectoryAttribute;
use Marktaborosi\StorageBrowser\Entities\FileAttribute;
use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\Tests\Traits\StorageTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class PHPNativeAdapterIntegrationTest
 *
 * This class contains integration tests for the PHPNativeAdapter.
 * These tests validate the functionality of the adapter against a real filesystem.
 *
 * @package Marktaborosi\Tests\Integration\Adapter
 */
class PHPNativeAdapterIntegrationTest extends TestCase
{
    use StorageTrait;

    /**
     * Tests that the `getFileStructure` method correctly retrieves the structure of files
     * and directories from the real filesystem.
     *
     * This test validates the creation of a `FileStructure` object containing the expected
     * directories and files, ensuring proper handling of file attributes and directory attributes.
     */
    public function test_get_file_structure_returns_correct_structure_from_real_native_storage(): void
    {
        // Prepare storage contents by creating test files and directories
        $createdFiles = self::createFiles(['file1.txt', 'TEST'], ['file2.txt', 'TEST2']);
        $createdDirectories = self::createDirectories('dir1');

        $storagePath = str_replace("\\", "/", self::rootStoragePath());

        // Expected structures based on the created files and directories
        $expectedFile1 = new FileAttribute(
            directoryPath: $storagePath,
            filename: "file1.txt",
            extension: "txt",
            byteSize: filesize($createdFiles[0]), // 'TEST' has 4 bytes
            lastModified: filemtime($createdFiles[0])
        );

        $expectedFile2 = new FileAttribute(
            directoryPath: $storagePath,
            filename: "file2.txt",
            extension: "txt",
            byteSize: filesize($createdFiles[1]), // 'TEST2' has 5 bytes
            lastModified: filemtime($createdFiles[1])
        );

        $lModify = filemtime(self::rootStoragePath() . "dir1");

        $expectedDirectory = new DirectoryAttribute(
            name: "dir1",
            path: $storagePath,
            lastModified: $lModify
        );

        // Build the expected file structure
        $fileStructureBuilder = new FileStructureBuilder();
        $fileStructureBuilder->addFile($expectedFile1);
        $fileStructureBuilder->addFile($expectedFile2);
        $fileStructureBuilder->addDirectory($expectedDirectory);
        $expectedFileStructure = $fileStructureBuilder->sortBy()->build();

        // Test the actual adapter with the real filesystem
        $adapter = new PHPNativeAdapter();
        $structure = $adapter->getFileStructure(self::rootStoragePath());

        // Asserts
        $this->assertInstanceOf(FileStructure::class, $structure);

        $this->assertCount(1, $structure->getDirectories());
        $this->assertInstanceOf(DirectoryAttribute::class, $structure->getDirectories()[0]);

        $this->assertCount(2, $structure->getFiles());
        $this->assertInstanceOf(FileAttribute::class, $structure->getFiles()[1]);
        $this->assertInstanceOf(FileAttribute::class, $structure->getFiles()[2]);

        $this->assertEquals($expectedFileStructure, $structure);

        // Clean up created files and directories
        self::deleteFiles($createdFiles);
        self::deleteDirectories($createdDirectories);
    }

    /**
     * Tests that the `fileOrDirectoryExists` method correctly identifies the existence of a file
     * in the real filesystem.
     *
     * This test ensures the adapter can accurately determine if a given file exists.
     */
    public function test_file_or_directory_exists_with_real_file(): void
    {
        // Create a real file in the storage path
        $createdFiles = self::createFiles(["file1.png", "test_content"]);

        // Test if the adapter correctly identifies the file existence
        $adapter = new PHPNativeAdapter();
        $exists = $adapter->fileOrDirectoryExists($createdFiles[0]);

        $this->assertTrue($exists);

        // Clean up created files
        self::deleteFiles($createdFiles);
    }

    /**
     * Tests that the `fileOrDirectoryExists` method correctly identifies the existence of a directory
     * in the real filesystem.
     *
     * This test ensures the adapter can accurately determine if a given directory exists.
     */
    public function test_file_or_directory_exists_with_real_directory(): void
    {
        // Create a real directory in the storage path
        $createdDirectories = self::createDirectories('dir1');

        // Test if the adapter correctly identifies the directory existence
        $adapter = new PHPNativeAdapter();
        $exists = $adapter->fileOrDirectoryExists($createdDirectories[0]);

        $this->assertTrue($exists);

        // Clean up created directories
        self::deleteDirectories($createdDirectories);
    }

    /**
     * Tests that the `fileOrDirectoryExists` method correctly identifies the existence of a directory
     * in the real filesystem.
     *
     * This test ensures the adapter can accurately determine if a given directory exists.
     */
    public function test_file_or_directory_not_exists(): void
    {
        $adapter = new PHPNativeAdapter();
        $exists = $adapter->fileOrDirectoryExists('not-existing-directory');

        $this->assertFalse($exists);
    }
}
