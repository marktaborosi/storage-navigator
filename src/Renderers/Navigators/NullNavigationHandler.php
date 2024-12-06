<?php

namespace Marktaborosi\StorageNavigator\Renderers\Navigators;

use Marktaborosi\StorageNavigator\Interfaces\StorageNavigatorActionHandlerInterface;

/**
 * Class NullNavigationHandler
 *
 * A no-operation implementation of the FileBrowserNavigationHandlerInterface.
 * This class is used when no navigation handling is required or desired.
 * All methods return default values indicating no navigation or file download action.
 *
 * @package Marktaborosi\StorageBrowser\Renderers\Navigators
 * @pattern Strategy
 *
 */
class NullNavigationHandler implements StorageNavigatorActionHandlerInterface
{
    /**
     * Indicates that no change path request is detected.
     *
     * @return bool Always returns false, indicating no change path request.
     */
    public function isChangePathRequest(): bool
    {
        return false;
    }

    /**
     * Indicates that no download file request is detected.
     *
     * @return bool Always returns false, indicating no download file request.
     */
    public function isDownloadFileRequest(): bool
    {
        return false;
    }

    /**
     * Returns an empty string, indicating no path change is available.
     *
     * @return string Always returns an empty string.
     */
    public function changeToPath(): string
    {
        return '';
    }

    /**
     * Returns an empty string, indicating no file path is available for download.
     *
     * @return string Always returns an empty string.
     */
    public function downloadFilePath(): string
    {
        return '';
    }
}
