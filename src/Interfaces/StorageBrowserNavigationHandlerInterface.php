<?php

namespace Marktaborosi\StorageBrowser\Interfaces;

/**
 * Interface for handling storage browser navigation requests.
 *
 * @pattern Strategy
 *
 */
interface StorageBrowserNavigationHandlerInterface
{
    /**
     * Determines if the current request is a request to change the directory path.
     *
     * @return bool Returns true if the request is to change the directory path, false otherwise.
     */
    public function isChangePathRequest(): bool;

    /**
     * Determines if the current request is a request to download a file.
     *
     * @return bool Returns true if the request is to download a file, false otherwise.
     */
    public function isDownloadFileRequest(): bool;

    /**
     * Retrieves the path to change to in a change path request.
     *
     * @return string Returns the new directory path to change to.
     */
    public function changeToPath(): string;

    /**
     * Retrieves the file path for a download file request.
     *
     * @return string Returns the file path for downloading.
     */
    public function downloadFilePath(): string;
}
