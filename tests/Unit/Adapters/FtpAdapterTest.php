<?php

namespace Marktaborosi\StorageNavigator\Tests\Unit\Adapters;

use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Marktaborosi\StorageNavigator\Adapters\FtpAdapter;
use Marktaborosi\StorageNavigator\Adapters\FTP\FtpConnection;
use Marktaborosi\StorageNavigator\Entities\FileStructure;

/**
 * Class FtpAdapterTest
 *
 * This class contains unit tests for the FtpAdapter.
 * It uses mocks to simulate the behavior of FTP connections and validates the functionality of the adapter methods.
 *
 * @package Marktaborosi\Tests\Unit\Adapter
 */
class FtpAdapterTest extends MockeryTestCase
{
    /**
     * Tests that the `initialize` method throws an exception when the FTP connection fails.
     *
     * This test ensures the adapter correctly handles a failed connection attempt.
     */
    public function test_initialize_throws_exception_when_connection_fails(): void
    {
        $ftpMock = Mockery::mock(FtpConnection::class);
        $ftpMock->shouldReceive('connect')->with('host', 21)->andReturn(false);
        $ftpMock->shouldReceive('close')->once();

        $adapter = new FtpAdapter($ftpMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not connect to FTP server.');

        $adapter->initialize('host', 'username', 'password');
    }

    /**
     * Tests that the `initialize` method throws an exception when FTP login fails.
     *
     * This test ensures the adapter correctly handles a failed login attempt.
     */
    public function test_initialize_throws_exception_when_login_fails(): void
    {
        $ftpMock = Mockery::mock(FtpConnection::class);
        $ftpMock->shouldReceive('connect')->with('host', 21)->andReturn(true);
        $ftpMock->shouldReceive('login')->with('username', 'password')->andReturn(false);
        $ftpMock->shouldReceive('close')->once();

        $adapter = new FtpAdapter($ftpMock);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('FTP login failed.');

        $adapter->initialize('host', 'username', 'password');
    }

    /**
     * Tests that the `getFileStructure` method retrieves the correct file structure from an FTP server.
     *
     * This test ensures that the adapter can parse the directory structure and identify files.
     *
     * @throws Exception
     */
    public function test_get_file_structure(): void
    {
        $ftpMock = Mockery::mock(FtpConnection::class);

        // Mock the connection sequence
        $ftpMock->shouldReceive('connect')->once()->with('host', 21)->andReturn(true);
        $ftpMock->shouldReceive('login')->once()->with('username', 'password')->andReturn(true);
        $ftpMock->shouldReceive('pasv')->with(true)->andReturn(true);

        // Mock directory listing with three entries: a directory and two files
        $ftpMock->shouldReceive('nlist')->with('/')->andReturn(['readme1.txt', 'readme2.txt']);

        // Mock size and last modified time for files
        $ftpMock->shouldReceive('size')->andReturn(100);
        $ftpMock->shouldReceive('mdtm')->andReturn(time());

        // Mock chdir behavior to return true for the directory 'pub' and false for files
        $ftpMock->shouldReceive('chdir')->andReturn(false);

        // Mock pwd to return the current directory
        $ftpMock->shouldReceive('pwd')->andReturn('/');

        // Mock closing the connection
        $ftpMock->shouldReceive('close')->once();

        // Initialize the adapter
        $adapter = new FtpAdapter($ftpMock);
        $adapter->initialize('host', 'username', 'password');

        // Get the file structure
        $fileStructure = $adapter->getFileStructure('/');

        // Assertions
        $this->assertInstanceOf(FileStructure::class, $fileStructure);
        $this->assertCount(2, $fileStructure->getFiles()); // Two files: 'readme1.txt' and 'readme2.txt'
    }

    /**
     * Tests that the `fileOrDirectoryExists` method correctly identifies the existence of a directory.
     *
     * @throws Exception
     */
    public function test_file_or_directory_exists_for_directory(): void
    {
        $ftpMock = Mockery::mock(FtpConnection::class);
        $ftpMock->shouldReceive('connect')->once()->with('host', 21)->andReturn(true);
        $ftpMock->shouldReceive('login')->once()->with('username', 'password')->andReturn(true);
        $ftpMock->shouldReceive('pasv')->with(true)->andReturn(true);
        $ftpMock->shouldReceive('chdir')->with('/root')->andReturn(true);
        $ftpMock->shouldReceive('chdir')->with('/rootdir')->andReturn(true);

        $ftpMock->shouldReceive('close')->once();

        $adapter = new FtpAdapter($ftpMock, '/root');
        $adapter->initialize('host', 'username', 'password');

        $result = $adapter->fileOrDirectoryExists('dir');

        $this->assertTrue($result);
    }

    /**
     * Tests that the `fileOrDirectoryExists` method correctly identifies the existence of a file.
     *
     * @throws Exception
     */
    public function test_file_or_directory_exists_for_file(): void
    {
        $ftpMock = Mockery::mock(FtpConnection::class);
        $ftpMock->shouldReceive('connect')->once()->with('host', 21)->andReturn(true);
        $ftpMock->shouldReceive('login')->once()->with('username', 'password')->andReturn(true);
        $ftpMock->shouldReceive('pasv')->with(true)->andReturn(true);
        $ftpMock->shouldReceive('chdir')->with('/rootfile.txt')->andReturn(false);
        $ftpMock->shouldReceive('size')->with('/rootfile.txt')->andReturn(100);

        $ftpMock->shouldReceive('close')->once();

        $adapter = new FtpAdapter($ftpMock, '/root');
        $adapter->initialize('host', 'username', 'password');

        $result = $adapter->fileOrDirectoryExists('file.txt');

        $this->assertTrue($result);
    }

    /**
     * Tests that the `downloadFile` method successfully downloads a file from the FTP server.
     *
     * This test ensures that the adapter correctly retrieves a file and outputs its content.
     *
     * @throws Exception
     */
    public function test_download_file(): void
    {
        // Create a temporary file to simulate the local file
        $tempFile = tempnam(sys_get_temp_dir(), 'ftp_test_');

        $ftpMock = Mockery::mock(FtpConnection::class);
        $ftpMock->shouldReceive('connect')->once()->with('host', 21)->andReturn(true);
        $ftpMock->shouldReceive('login')->once()->with('username', 'password')->andReturn(true);
        $ftpMock->shouldReceive('pasv')->with(true)->andReturn(true);
        $ftpMock->shouldReceive('get')->with(Mockery::type('string'), '/remote/file.txt')->andReturnUsing(function ($localFile) use ($tempFile) {
            // Simulate downloading the file by writing some content to the local file
            file_put_contents($localFile, 'Test file content');
            return true;
        });
        $ftpMock->shouldReceive('close')->once();

        $adapter = new FtpAdapter($ftpMock);
        $adapter->initialize('host', 'username', 'password');

        // Start output buffering to capture file content
        ob_start();
        $adapter->downloadFile('/remote/file.txt');
        $output = ob_get_clean();

        // Assert that file content is correctly output
        $this->assertStringContainsString('Test file content', $output, 'File content does not match.');

        // Clean up the temporary file if necessary
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }
}
