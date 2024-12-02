<?php

namespace Marktaborosi\Tests\Unit;

use Exception;
use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserAdapterInterface;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserNavigationHandlerInterface;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserRendererInterface;
use Marktaborosi\StorageBrowser\Renderers\Entities\RenderData;
use Marktaborosi\StorageBrowser\StorageBrowser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Class StorageBrowserTest
 *
 * This class contains unit tests for the `StorageBrowser` class, ensuring that it behaves
 * correctly under various scenarios. It tests the constructor, rendering functionalities,
 * request handling, and exception handling mechanisms of the `StorageBrowser`.
 *
 * @package Unit
 */
class StorageBrowserTest extends TestCase
{
    /**
     * A mocked instance of the `StorageBrowserAdapterInterface` used to simulate
     * interactions with the storage system without relying on a real filesystem.
     */
    private StorageBrowserAdapterInterface|MockObject $adapterMock;

    /**
     * A mocked instance of the `StorageBrowserRendererInterface` used to simulate
     * the rendering process without producing actual output.
     */
    private StorageBrowserRendererInterface|MockObject $rendererMock;

    /**
     * A mocked instance of the `FileBrowserConfig` used to provide configuration
     * settings to the `StorageBrowser` without relying on actual configuration files.
     */
    private FileBrowserConfig|MockObject $configMock;

    /**
     * A mocked instance of the `StorageBrowserNavigationHandlerInterface` used to
     * simulate navigation handling within the `StorageBrowser`.
     */
    private StorageBrowserNavigationHandlerInterface|MockObject $navigationHandlerMock;

    /**
     * Sets up the test environment by creating mocks for all dependencies required by the `StorageBrowser`.
     *
     * This method is called before each test method is executed, ensuring that each test starts
     * with a fresh set of mocked dependencies. It prevents tests from affecting each other
     * by providing isolated environments.
     *
     * @throws Exception|\PHPUnit\Framework\MockObject\Exception If mock creation fails.
     */
    protected function setUp(): void
    {
        // Create mocks for dependencies
        $this->adapterMock = $this->createMock(StorageBrowserAdapterInterface::class);
        $this->rendererMock = $this->createMock(StorageBrowserRendererInterface::class);
        $this->configMock = $this->createMock(FileBrowserConfig::class);
        $this->navigationHandlerMock = $this->createMock(StorageBrowserNavigationHandlerInterface::class);

        parent::setUp();
    }

    /**
     * Tests that the constructor of `StorageBrowser` throws an exception when the specified root path does not exist.
     *
     * This test verifies that the `StorageBrowser` correctly validates the existence of the root path
     * during instantiation and responds appropriately by throwing an exception if the path is invalid.
     *
     * @throws Exception When the root path does not exist.
     */
    public function testConstructorThrowsExceptionWhenRootPathDoesNotExist()
    {
        // Mock the adapter to return false for file or directory existence
        $this->adapterMock->method('fileOrDirectoryExists')->willReturn(false);

        // Expect an exception to be thrown due to non-existent root path
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Location: [/invalid/path] does not exist');

        // Instantiate StorageBrowser with an invalid root path
        new StorageBrowser(
            $this->adapterMock,
            $this->rendererMock,
            $this->configMock,
            '/invalid/path'
        );
    }

    /**
     * Tests the rendering of the root directory by the `StorageBrowser`.
     *
     * This test ensures that when the `StorageBrowser` is initialized with a valid root path,
     * it correctly invokes the renderer to display the root directory. It verifies that the
     * renderer's `render` method is called with an instance of `RenderData`.
     *
     * @throws Exception If an error occurs during rendering.
     */
    public function testRenderRootDirectory(): void
    {
        // Mock the adapter to return true for root path existence
        $this->adapterMock->method('fileOrDirectoryExists')->willReturn(true);

        // Mock the renderer to return the mocked navigation handler
        $this->rendererMock->method('navigationHandler')->willReturn($this->navigationHandlerMock);

        // Mock navigation handler behavior to indicate no special requests
        $this->navigationHandlerMock->method('isChangePathRequest')->willReturn(false);
        $this->navigationHandlerMock->method('isDownloadFileRequest')->willReturn(false);

        // Expect the renderer's render method to be called once with a valid RenderData instance
        $this->rendererMock->expects($this->once())
            ->method('render')
            ->with($this->callback(function ($renderData) {
                return $renderData instanceof RenderData;
            }));

        // Instantiate and call display on the StorageBrowser
        $storageBrowser = new StorageBrowser(
            $this->adapterMock,
            $this->rendererMock,
            $this->configMock,
            "/valid/path"
        );

        $storageBrowser->display();
    }

    /**
     * Tests that the `display` method of `StorageBrowser` invokes the `handleRequest` method correctly.
     *
     * This test ensures that when the `display` method is called, it processes the request
     * appropriately by delegating to the renderer and handling navigation without errors.
     *
     * @throws Exception If an error occurs during request handling.
     */
    public function testDisplayCallsHandleRequest(): void
    {
        // Mock the adapter to return true for file or directory existence
        $this->adapterMock->method('fileOrDirectoryExists')->willReturn(true);

        // Mock the renderer to return the mocked navigation handler
        $this->rendererMock->method('navigationHandler')->willReturn($this->navigationHandlerMock);

        // Mock navigation handler behavior to indicate no special requests
        $this->navigationHandlerMock->method('isChangePathRequest')->willReturn(false);
        $this->navigationHandlerMock->method('isDownloadFileRequest')->willReturn(false);

        // Expect the renderer's render method to be called once with a valid RenderData instance
        $this->rendererMock->expects($this->once())
            ->method('render')
            ->with($this->callback(function ($renderData) {
                return $renderData instanceof RenderData;
            }));

        // Instantiate and call display on the StorageBrowser
        $storageBrowser = new StorageBrowser(
            $this->adapterMock,
            $this->rendererMock,
            $this->configMock,
            '/valid/path'
        );

        $storageBrowser->display();
    }

    /**
     * Tests that the `handleRequest` method correctly processes a change path request.
     *
     * This test verifies that when a change path request is detected, the `StorageBrowser`
     * updates its current path accordingly and invokes the renderer to display the new path.
     *
     * @throws Exception If an error occurs during path change or rendering.
     */
    public function testHandleRequestRendersChangePath(): void
    {
        // Mock the adapter to return true for file or directory existence
        $this->adapterMock->method('fileOrDirectoryExists')->willReturn(true);

        // Mock the renderer to return the mocked navigation handler
        $this->rendererMock->method('navigationHandler')->willReturn($this->navigationHandlerMock);

        // Mock the navigation handler to indicate a path change request
        $this->navigationHandlerMock->method('isChangePathRequest')->willReturn(true);
        $this->navigationHandlerMock->method('changeToPath')->willReturn('/valid/new/path');

        // Expect the renderer's render method to be called once with a valid RenderData instance
        $this->rendererMock->expects($this->once())
            ->method('render')
            ->with($this->callback(function ($renderData) {
                return $renderData instanceof RenderData;
            }));

        // Instantiate and call display on the StorageBrowser
        $storageBrowser = new StorageBrowser(
            $this->adapterMock,
            $this->rendererMock,
            $this->configMock,
            "/valid/path"
        );

        $storageBrowser->display();
    }

    /**
     * Tests that the `handleRequest` method correctly processes a download file request.
     *
     * This test ensures that when a download file request is detected, the `StorageBrowser`
     * invokes the adapter's `downloadFile` method with the correct file path, facilitating
     * the file download process.
     *
     * @throws Exception If an error occurs during file download.
     */
    public function testHandleRequestRendersIsDownloadFileRequest(): void
    {
        // Mock the adapter to return true for file or directory existence
        $this->adapterMock->expects($this->exactly(2))->method('fileOrDirectoryExists')->willReturn(true);

        // Mock the renderer to return the mocked navigation handler
        $this->rendererMock->method('navigationHandler')->willReturn($this->navigationHandlerMock);

        // Mock the navigation handler to indicate a file download request
        $this->navigationHandlerMock->method('isChangePathRequest')->willReturn(false);
        $this->navigationHandlerMock->method('isDownloadFileRequest')->willReturn(true);

        // Define the expected file path to be downloaded
        $expectedFilePath = '/new/path/file';
        $this->navigationHandlerMock->method('downloadFilePath')->willReturn($expectedFilePath);

        // Expect the adapter's downloadFile method to be called once with the expected file path
        $this->adapterMock->expects($this->once())
            ->method("downloadFile")
            ->with($expectedFilePath);

        // Instantiate and call display on the StorageBrowser
        $storageBrowser = new StorageBrowser(
            $this->adapterMock,
            $this->rendererMock,
            $this->configMock,
            "/valid/path"
        );

        $storageBrowser->display();
    }

    /**
     * Tests that the `handleRequest` method throws an exception when attempting to download a non-existent file.
     *
     * This test verifies that the `StorageBrowser` correctly handles scenarios where a download
     * request is made for a file that does not exist. It ensures that an appropriate exception
     * is thrown, preventing undefined behavior.
     *
     * @throws Exception If the file to download does not exist.
     */
    public function testHandleRequestThrowsExceptionIfDownloadFileDoesNotExists(): void
    {
        $nonExistentFilePath = '/invalid/path/file';
        $validRootPath = '/valid/path';

        // Mock the adapter to return different values based on the path
        $this->adapterMock->method('fileOrDirectoryExists')->willReturnMap([
            [$validRootPath, true],
            [$nonExistentFilePath, false]
        ]);

        // Mock the renderer to return the mocked navigation handler
        $this->rendererMock->method('navigationHandler')->willReturn($this->navigationHandlerMock);

        // Mock the navigation handler to indicate a file download request
        $this->navigationHandlerMock->method('isChangePathRequest')->willReturn(false);
        $this->navigationHandlerMock->method('isDownloadFileRequest')->willReturn(true);
        $this->navigationHandlerMock->method('downloadFilePath')->willReturn($nonExistentFilePath);

        // Expect an exception to be thrown with a specific message
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File [' . $nonExistentFilePath . '] does not exist.');

        // Instantiate and call display on the StorageBrowser
        $storageBrowser = new StorageBrowser(
            $this->adapterMock,
            $this->rendererMock,
            $this->configMock,
            $validRootPath
        );

        $storageBrowser->display();
    }

    /**
     * Tests that the constructor of `StorageBrowser` throws a `TypeError` when an invalid class type is passed.
     *
     * This test ensures that the `StorageBrowser` enforces type safety by throwing a `TypeError`
     * when dependencies of incorrect types are provided, preventing runtime errors and ensuring
     * proper usage of the class.
     *
     * @throws Exception|TypeError If an invalid class type is passed to the constructor.
     */
    public function testConstructorThrowsExceptionWithInvalidClass(): void
    {
        // Expect a TypeError to be thrown due to invalid adapter type
        $this->expectException(TypeError::class);

        // Attempt to instantiate StorageBrowser with a null adapter, which is invalid
        new StorageBrowser(
            null, // Invalid adapter type
            $this->rendererMock,
            $this->configMock,
            '/valid/path'
        );
    }
}
