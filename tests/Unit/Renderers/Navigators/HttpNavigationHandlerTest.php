<?php

namespace Marktaborosi\Tests\Unit\Renderers\Navigators;

use Marktaborosi\StorageBrowser\Renderers\Navigators\HttpNavigationHandler;
use PHPUnit\Framework\TestCase;

/**
 * Class HttpNavigationHandlerTest
 *
 * This class contains unit tests for the `HttpNavigationHandler` class.
 * It verifies that the navigation handler correctly identifies navigation
 * requests, retrieves request parameters, and ensures proper cleanup of
 * global variables after each test.
 *
 * @package Marktaborosi\Tests\Unit\Renderers\Navigators
 */
class HttpNavigationHandlerTest extends TestCase
{
    /**
     * Tests that `isChangePathRequest()` returns true when the request method is POST
     * and the 'action' parameter is set to 'changePath'.
     *
     * Simulates a POST request with the appropriate parameters and ensures the
     * method detects it as a "change path" request.
     */
    public function test_is_change_path_request_returns_true(): void
    {
        // Simulate a POST request with 'changePath' action
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = 'changePath';

        $handler = new HttpNavigationHandler();

        $this->assertTrue($handler->isChangePathRequest());
    }

    /**
     * Tests that `isChangePathRequest()` returns false when the 'action' parameter
     * is not 'changePath'.
     *
     * Simulates a POST request with a different action and ensures the method
     * does not incorrectly identify it as a "change path" request.
     */
    public function test_is_change_path_request_returns_false(): void
    {
        // Simulate a POST request with a different action
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = 'someOtherAction';

        $handler = new HttpNavigationHandler();

        $this->assertFalse($handler->isChangePathRequest());
    }

    /**
     * Tests that `isDownloadFileRequest()` returns true when the request method is POST
     * and the 'action' parameter is set to 'downloadFile'.
     *
     * Simulates a POST request with the appropriate parameters and ensures the
     * method detects it as a "download file" request.
     */
    public function test_is_download_file_request_returns_true(): void
    {
        // Simulate a POST request with 'downloadFile' action
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = 'downloadFile';

        $handler = new HttpNavigationHandler();

        $this->assertTrue($handler->isDownloadFileRequest());
    }

    /**
     * Tests that `isDownloadFileRequest()` returns false when the 'action' parameter
     * is not 'downloadFile'.
     *
     * Simulates a POST request with a different action and ensures the method
     * does not incorrectly identify it as a "download file" request.
     */
    public function test_is_download_file_request_returns_false(): void
    {
        // Simulate a POST request with a different action
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = 'someOtherAction';

        $handler = new HttpNavigationHandler();

        $this->assertFalse($handler->isDownloadFileRequest());
    }

    /**
     * Tests that `changeToPath()` correctly retrieves the 'path' parameter from the POST request.
     *
     * Simulates a POST request with a 'path' parameter and verifies that the
     * method retrieves the correct value.
     */
    public function test_change_to_path_returns_correct_path(): void
    {
        // Simulate a POST request with a 'path' parameter
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['path'] = '/new/path';

        $handler = new HttpNavigationHandler();

        $this->assertSame('/new/path', $handler->changeToPath());
    }

    /**
     * Tests that `downloadFilePath()` correctly retrieves the 'file' parameter from the POST request.
     *
     * Simulates a POST request with a 'file' parameter and verifies that the
     * method retrieves the correct value.
     */
    public function test_download_file_path_returns_correct_path(): void
    {
        // Simulate a POST request with a 'file' parameter
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['file'] = '/path/to/file.txt';

        $handler = new HttpNavigationHandler();

        $this->assertSame('/path/to/file.txt', $handler->downloadFilePath());
    }

    /**
     * Cleans up the $_SERVER and $_POST global variables after each test to ensure
     * no data leakage between tests.
     *
     * Resets the global variables used for simulating requests to their default state,
     * ensuring test independence.
     */
    protected function tear_down(): void
    {
        $_SERVER = [];
        $_POST = [];
    }
}
