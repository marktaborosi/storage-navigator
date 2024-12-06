<?php

namespace Marktaborosi\StorageNavigator\Tests\Integration\Adapter;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\StorageNavigator\Adapters\FlysystemAdapter;
use Marktaborosi\StorageNavigator\Builders\FileStructureBuilder;
use Marktaborosi\StorageNavigator\Entities\DirectoryAttribute;
use Marktaborosi\StorageNavigator\Entities\FileAttribute;
use Marktaborosi\StorageNavigator\Entities\FileStructure;
use Marktaborosi\StorageNavigator\Tests\Traits\StorageTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class FlysystemAdapterIntegrationTest
 *
 * This class contains integration tests for the FlysystemAdapter.
 * These tests validate the interaction between FlysystemAdapter and a real filesystem.
 *
 * @package Marktaborosi\Tests\Integration
 */
class FlysystemAdapterIntegrationTest extends TestCase
{
    use StorageTrait;

    /**
     * @var Filesystem Filesystem instance configured with a real filesystem adapter.
     */
    private Filesystem $adapterWithRealFilesystem;

    /**
     * Sets up the test environment before each test.
     *
     * Configures the FilesystemAdapter with a real filesystem for integration testing.
     */
    protected function setUp(): void
    {
        // Set up the real filesystem for integration tests.
        $localAdapter = new LocalFilesystemAdapter(self::rootStoragePath());
        $this->adapterWithRealFilesystem = new Filesystem($localAdapter);
    }

    /**
     * Tests that `getFileStructure` returns the correct structure using a real filesystem.
     *
     * This test verifies that the `getFileStructure` method correctly builds a FileStructure
     * when interacting with a real filesystem, including proper handling of files and directories.
     *
     * @throws FilesystemException
     */
    public function test_get_file_structure_returns_correct_structure_from_real_filesystem_storage(): void
    {
        // Prepare storage contents by creating test files and directories
        $createdFiles = self::createFiles(['file1.txt', 'TEST'], ['file2.txt', 'TEST2']);
        $createdDirectories = self::createDirectories('dir1');

        // Expected structures based on the created files and directories
        $expectedFile1 = new FileAttribute(
            directoryPath: "",
            filename: "file1.txt",
            extension: "txt",
            byteSize: filesize($createdFiles[0]), // 'TEST' has 4 bytes
            lastModified: filemtime($createdFiles[0])
        );

        $expectedFile2 = new FileAttribute(
            directoryPath: "",
            filename: "file2.txt",
            extension: "txt",
            byteSize: filesize($createdFiles[1]), // 'TEST2' has 5 bytes
            lastModified: filemtime($createdFiles[1])
        );

        $lModify = filemtime(self::rootStoragePath() . "dir1");

        $expectedDirectory = new DirectoryAttribute(
            name: "dir1",
            path: "",
            lastModified: $lModify
        );

        // Build the expected file structure
        $fileStructureBuilder = new FileStructureBuilder();
        $fileStructureBuilder->addFile($expectedFile1);
        $fileStructureBuilder->addFile($expectedFile2);
        $fileStructureBuilder->addDirectory($expectedDirectory);
        $expectedFileStructure = $fileStructureBuilder->sortByAZ()->build();

        // Test the actual adapter with real filesystem
        $adapter = new FlysystemAdapter($this->adapterWithRealFilesystem);
        $structure = $adapter->getFileStructure("");

        // Asserts
        $this->assertInstanceOf(FileStructure::class, $structure);

        $this->assertCount(1, $structure->getDirectories());
        $this->assertInstanceOf(DirectoryAttribute::class, $structure->getDirectories()[0]);

        $this->assertCount(2, $structure->getFiles());
        $this->assertInstanceOf(FileAttribute::class, $structure->getFiles()[1]);
        $this->assertInstanceOf(FileAttribute::class, $structure->getFiles()[2]);

        $this->assertEquals($expectedFileStructure, $structure);

        self::deleteFiles($createdFiles);
        self::deleteDirectories($createdDirectories);
    }

    /**
     * Tests that the `fileOrDirectoryExists` method correctly identifies the existence of a file
     * using a real filesystem.
     *
     * @throws FilesystemException
     */
    public function test_file_or_directory_exists_with_real_file(): void
    {
        // Create a real file in the storage path
        $createdFiles = self::createFiles(["file1.png", "test_content"]);

        // Test if the adapter correctly identifies the file existence
        $exists = $this->adapterWithRealFilesystem->fileExists(basename($createdFiles[0]));

        $this->assertTrue($exists);

        self::deleteFiles($createdFiles);
    }

    /**
     * Tests that the `fileOrDirectoryExists` method correctly identifies the existence of a directory
     * using a real filesystem.
     *
     * @throws FilesystemException
     */
    public function test_file_or_directory_exists_with_real_directory(): void
    {
        // Create a real directory in the storage path
        $createdDirectories = self::createDirectories('dir1');

        // Test if the adapter correctly identifies the directory existence
        $exists = $this->adapterWithRealFilesystem->directoryExists(basename($createdDirectories[0]));

        $this->assertTrue($exists);

        self::deleteDirectories($createdDirectories);
    }

    /**
     * Tests that the `fileOrDirectoryExists` method correctly identifies the non-existence of a directory
     * using a real filesystem.
     *
     * @throws FilesystemException
     */
    public function test_not_existing_file_or_directory_not_exists(): void
    {
        // Test if the adapter correctly identifies the directory existence
        $exists = $this->adapterWithRealFilesystem->directoryExists('not-existing-file');

        $this->assertFalse($exists);
    }
}
