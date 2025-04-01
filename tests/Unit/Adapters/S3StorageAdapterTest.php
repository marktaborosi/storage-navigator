<?php

namespace Marktaborosi\StorageNavigator\Tests\Unit\Adapters;

use Mockery;
use PHPUnit\Framework\TestCase;
use Aws\S3\S3Client;
use Marktaborosi\StorageNavigator\Adapters\S3\S3StorageAdapter;
use Marktaborosi\StorageNavigator\Entities\FileStructure;

class S3StorageAdapterTest extends TestCase
{
    /**
     * Mock the S3 client and test the getFileStructure method
     */
    public function testGetFileStructure()
    {
        // Mock the S3Client
        $mockS3Client = Mockery::mock(S3Client::class);

        // Define the expected behavior for the listObjectsV2 method
        $mockS3Client->shouldReceive('listObjectsV2')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Prefix' => 'some/prefix/',
                'Delimiter' => '/',
            ])
            ->andReturn([
                'CommonPrefixes' => [
                    ['Prefix' => 'some/prefix/directory1/'],
                    ['Prefix' => 'some/prefix/directory2/'],
                ],
                'Contents' => [
                    [
                        'Key' => 'some/prefix/file1.txt',
                        'Size' => 1234,
                        'LastModified' => new \DateTime(),
                    ],
                    [
                        'Key' => 'some/prefix/file2.txt',
                        'Size' => 5678,
                        'LastModified' => new \DateTime(),
                    ],
                ],
            ]);

        // Instantiate the S3StorageAdapter with the mocked client
        $adapter = new S3StorageAdapter('test-bucket', $mockS3Client);

        // Call the method we're testing
        $fileStructure = $adapter->getFileStructure('some/prefix');

        // Check the structure of files and directories
        $this->assertInstanceOf(FileStructure::class, $fileStructure);
        $this->assertCount(2, $fileStructure->getDirectories());
        $this->assertCount(2, $fileStructure->getFiles());

        // Verify that directories are correctly added
        $directories = $fileStructure->getDirectories();
        $this->assertEquals('directory1', $directories[0]->getName());
        $this->assertEquals('directory2', $directories[1]->getName());

        // Verify that files are correctly added
        $files = $fileStructure->getFiles();
        $this->assertEquals('file1.txt', $files[2]->getFilename());
        $this->assertEquals('file2.txt', $files[3]->getFilename());
    }

    /**
     * Mock the S3 client and test the fileOrDirectoryExists method for files
     */
    public function testFileOrDirectoryExistsFile()
    {
        // Mock the S3Client
        $mockS3Client = Mockery::mock(S3Client::class);

        // Define the expected behavior for the headObject method (for files)
        $mockS3Client->shouldReceive('headObject')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => 'some/prefix/file1.txt',
            ])
            ->andReturn([
                'ResponseMetadata' => ['RequestId' => '123'],
            ]);

        // Instantiate the S3StorageAdapter with the mocked client
        $adapter = new S3StorageAdapter('test-bucket', $mockS3Client);

        // Call the method we're testing
        $exists = $adapter->fileOrDirectoryExists('some/prefix/file1.txt');

        // Assert that the file exists
        $this->assertTrue($exists);
    }

    /**
     * Mock the S3 client and test the downloadFile method
     */
    public function testDownloadFile()
    {
        // Mock the S3Client
        $mockS3Client = Mockery::mock(S3Client::class);

        // Define the expected behavior for the getObject method
        $mockS3Client->shouldReceive('getObject')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => 'some/prefix/file1.txt',
            ])
            ->andReturn([
                'ContentType' => 'text/plain',
                'ContentLength' => 1234,
                'Body' => 'Dummy content of the file',
            ]);

        // Instantiate the S3StorageAdapter with the mocked client
        $adapter = new S3StorageAdapter('test-bucket', $mockS3Client);

        // Create a PHP output buffer to capture the result of the download
        ob_start();
        $adapter->downloadFile('some/prefix/file1.txt');
        $output = ob_get_clean();

        // Check if the content matches the expected body
        $this->assertEquals('Dummy content of the file', $output);
    }

    /**
     * Close Mockery expectations
     */
    public function tearDown(): void
    {
        Mockery::close();
    }
}
