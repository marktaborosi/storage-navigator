<?php

namespace Marktaborosi\StorageNavigator\Interfaces;

/**
 * Interface StorageNavigatorActionHandlerInterface
 *
 * Defines the contract for handling navigation requests in a storage browser.
 * Implementations of this interface are responsible for determining the type
 * of navigation request (e.g., changing directory paths, downloading files)
 * and providing the necessary paths or actions based on the request.
 *
 * @package Marktaborosi\StorageBrowser\Interfaces
 * @pattern Strategy
 */
interface StorageNavigatorActionHandlerInterface
{
    /**
     * Determines if the current request is to change the directory path.
     *
     * This method checks the context of the current request to determine if it
     * represents a request to navigate to a different directory.
     *
     * @return bool Returns true if the request is to change the directory path, false otherwise.
     */
    public function isChangePathRequest(): bool;

    /**
     * Determines if the current request is to download a file.
     *
     * This method checks the context of the current request to determine if it
     * represents a request to download a specific file.
     *
     * @return bool Returns true if the request is to download a file, false otherwise.
     */
    public function isDownloadFileRequest(): bool;

    /**
     * Retrieves the target path for a change path request.
     *
     * If the current request is to navigate to a different directory, this method
     * provides the new directory path to which the browser should navigate.
     *
     * @return string Returns the directory path to change to.
     */
    public function changeToPath(): string;

    /**
     * Retrieves the file path for a download request.
     *
     * If the current request is to download a file, this method provides the file
     * path of the file to be downloaded.
     *
     * @return string Returns the file path for downloading.
     */
    public function downloadFilePath(): string;
}
