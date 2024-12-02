<?php

namespace Marktaborosi\StorageBrowser\Renderers\Navigators;

use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserNavigationHandlerInterface;
use RuntimeException;

/**
 * Class HttpNavigationHandler
 *
 * Handles file browser navigation based on HTTP POST requests.
 * This class is responsible for detecting navigation actions such as changing the path
 * or downloading a file from the browser's user interface.
 *
 * @package Marktaborosi\StorageBrowser\Renderers\Navigators
 * @pattern Strategy
 */
class HttpNavigationHandler implements StorageBrowserNavigationHandlerInterface
{

    public function __construct() {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            throw new RuntimeException("The HttpNavigationHandler requires an HTTP context. 'REQUEST_METHOD' is not set, indicating the script is not being run via a web server.");
        }
    }
    /**
     * Determines if the current request is a request to change the directory path.
     *
     * This method checks if the HTTP request is a POST request and if the action specified
     * in the POST data is 'changePath'.
     *
     * @return bool Returns true if the request is a POST request with the action 'changePath'; otherwise, false.
     */
    public function isChangePathRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST) && $_POST['action'] === 'changePath';
    }

    /**
     * Determines if the current request is a request to download a file.
     *
     * This method checks if the HTTP request is a POST request and if the action specified
     * in the POST data is 'downloadFile'.
     *
     * @return bool Returns true if the request is a POST request with the action 'downloadFile'; otherwise, false.
     */
    public function isDownloadFileRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST) && $_POST['action'] === 'downloadFile';
    }

    /**
     * Retrieves the directory path from the POST request when changing directories.
     *
     * This method extracts the 'path' parameter from the POST data, which specifies the
     * directory to navigate to.
     *
     * @return string The directory path to change to.
     */
    public function changeToPath(): string
    {
        return $_POST['path'];
    }

    /**
     * Retrieves the file path from the POST request when downloading a file.
     *
     * This method extracts the 'file' parameter from the POST data, which specifies the
     * file to be downloaded.
     *
     * @return string The file path to download.
     */
    public function downloadFilePath(): string
    {
        return $_POST['file'];
    }
}
