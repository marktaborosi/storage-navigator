<?php

namespace Marktaborosi\Tests\Unit\Adapters;

use Exception;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Marktaborosi\StorageBrowser\Adapters\FlysystemAdapter;
use Marktaborosi\StorageBrowser\Builders\FileStructureBuilder;
use Marktaborosi\StorageBrowser\Entities\DirectoryAttribute;
use Marktaborosi\StorageBrowser\Entities\FileAttribute;
use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\Tests\Traits\StorageTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class FilesystemAdapterTest
 *
 * This class contains unit tests for the `FilesystemAdapter` class.
 * It verifies the behavior of the adapter when interacting with mocked
 * and real filesystem components.
 *
 * @package Marktaborosi\Tests\Unit\Adapter
 */
class FlysystemAdapterTest extends TestCase
{
    use StorageTrait;

    private FilesystemOperator|MockObject $filesystemMock;
    private FlysystemAdapter $adapterWithMockedFlysystem;

    /**
     * Sets up the test environment before each test.
     *
     * Creates a mocked `FilesystemOperator` for testing and initializes
     * the `FilesystemAdapter` with the mocked filesystem.
     *
     * @throws Exception|\PHPUnit\Framework\MockObject\Exception If the mock cannot be created.
     */
    protected function setUp(): void
    {
        $this->filesystemMock = $this->createMock(FilesystemOperator::class);
        $this->adapterWithMockedFlysystem = new FlysystemAdapter($this->filesystemMock);
    }

    /**
     * Tests that `getFileStructure` returns the correct structure using a mocked filesystem.
     *
     * This test simulates the presence of specific files and directories and verifies
     * that the method constructs the expected `FileStructure`.
     *
     * @throws FilesystemException
     */
    public function test_get_file_structure_returns_correct_structure_mocked(): void
    {
        $lastModified = time();

        // Expected structure components
        $expectedFile = new FileAttribute(
            directoryPath: "",
            filename: "file1.txt",
            extension: "txt",
            byteSize: 100,
            lastModified: $lastModified
        );

        $expectedDirectory = new DirectoryAttribute(
            name: "dir1",
            path: "",
            lastModified: $lastModified
        );

        $fileStructureBuilder = new FileStructureBuilder();
        $fileStructureBuilder->addFile($expectedFile);
        $fileStructureBuilder->addDirectory($expectedDirectory);
        $expectedFileStructure = $fileStructureBuilder->sortBy()->build();

        // Mock filesystem behavior
        $file = new FileAttributes("file1.txt", 100, null, $lastModified, null);
        $dir = new DirectoryAttributes("dir1", null, $lastModified);
        $directoryListing = new DirectoryListing([$file, $dir]);

        $this->filesystemMock->method('listContents')
            ->with('', false)
            ->willReturn($directoryListing);

        $this->filesystemMock->method('fileSize')
            ->with('file1.txt')->willReturn(100);

        // Test the adapter's method
        $structure = $this->adapterWithMockedFlysystem->getFileStructure("");

        // Assertions
        $this->assertInstanceOf(FileStructure::class, $structure);
        $this->assertCount(1, $structure->getDirectories());
        $this->assertEquals($expectedDirectory, $structure->getDirectories()[0]);
        $this->assertCount(1, $structure->getFiles());
        $this->assertEquals($expectedFile, $structure->getFiles()[1]);
        $this->assertEquals($expectedFileStructure, $structure);
    }

    /**
     * Tests that `fileOrDirectoryExists` identifies file existence correctly using a mocked filesystem.
     *
     * @throws FilesystemException
     */
    public function test_file_or_directory_exists_with_file_mocked(): void
    {
        $this->filesystemMock->method('fileExists')
            ->with('some/path/file.txt')
            ->willReturn(true);

        $this->filesystemMock->method('directoryExists')
            ->with('some/path/file.txt')
            ->willReturn(false);

        $exists = $this->adapterWithMockedFlysystem->fileOrDirectoryExists('some/path/file.txt');
        $this->assertTrue($exists);
    }

    /**
     * Tests that `fileOrDirectoryExists` identifies directory existence correctly using a mocked filesystem.
     *
     * @throws FilesystemException
     */
    public function test_file_or_directory_exists_with_directory_mocked(): void
    {
        $this->filesystemMock->method('fileExists')
            ->with('some/path')
            ->willReturn(false);

        $this->filesystemMock->method('directoryExists')
            ->with('some/path')
            ->willReturn(true);

        $exists = $this->adapterWithMockedFlysystem->fileOrDirectoryExists('some/path');
        $this->assertTrue($exists);
    }

    /**
     * Tests that `getFileStructure` handles a `FilesystemException`.
     *
     * This test verifies that the method throws a `FilesystemException`
     * when the underlying filesystem encounters an error.
     *
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function test_get_file_structure_handles_filesystem_exception(): void
    {
        $filesystemExceptionMock = $this->createMock(FilesystemException::class);
        $this->filesystemMock->method('listContents')
            ->with('some/path', false)
            ->willThrowException($filesystemExceptionMock);

        $this->expectException(FilesystemException::class);
        $this->adapterWithMockedFlysystem->getFileStructure('some/path');
    }

    /**
     * Tests that `downloadFile` throws an exception for non-existent files.
     *
     * @throws FilesystemException
     */
    public function test_download_file_throws_exception_when_file_does_not_exist(): void
    {
        $filePath = 'some/nonexistent/path/file.txt';

        $this->filesystemMock->method('fileExists')
            ->with($filePath)
            ->willReturn(false);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("File does not exist.");
        $this->adapterWithMockedFlysystem->downloadFile($filePath);
    }

    /**
     * Tests that `downloadFile` successfully processes an existing file.
     *
     * Verifies that the method handles file download correctly, including
     * sending the appropriate headers.
     *
     * @throws FilesystemException
     */
    public function test_download_file_successful(): void
    {
        $filePath = 'some/path/file.txt';
        $mimeType = 'text/plain';
        $fileSize = 100;

        $this->filesystemMock->method('fileExists')
            ->with($filePath)
            ->willReturn(true);

        $this->filesystemMock->method('mimeType')
            ->with($filePath)
            ->willReturn($mimeType);

        $this->filesystemMock->method('fileSize')
            ->with($filePath)
            ->willReturn($fileSize);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'File content');
        rewind($stream);

        $this->filesystemMock->method('readStream')
            ->with($filePath)
            ->willReturn($stream);

        ob_start();
        $this->adapterWithMockedFlysystem->downloadFile($filePath);
        $output = ob_get_clean();

        $this->assertStringContainsString('File content', $output);
    }
}
