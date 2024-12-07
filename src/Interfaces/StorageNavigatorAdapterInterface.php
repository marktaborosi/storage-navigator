<?php
namespace Marktaborosi\StorageNavigator\Interfaces;

use Marktaborosi\StorageNavigator\Entities\FileStructure;

/**
 * Interface for storage browser adapters.
 *
 * This interface defines the methods required for implementing a storage browser adapter.
 * It includes functionalities for checking if a file or directory exists, retrieving
 * the structure of a specified location, and handling file downloads.
 *
 * @package Marktaborosi\StorageBrowser\Interfaces
 * @pattern Adapter / Facade
 */
interface StorageNavigatorAdapterInterface
{
    /**
     * Check if the specified location exists.
     *
     * This method checks whether a file or directory exists at the given location.
     *
     * @param string $location The path of the location to check.
     * @return bool Returns true if the location exists, false otherwise.
     */
    public function fileOrDirectoryExists(string $location): bool;

    /**
     * Get the structure of the specified location.
     *
     * This method retrieves the file and directory structure of a given location
     * and returns it as a `FileStructure` object.
     *
     * @param string $location The path of the location to retrieve the structure from.
     * @return FileStructure The structure of the specified location.
     */
    public function getFileStructure(string $location): FileStructure;

    /**
     * Handles the downloading of a file.
     *
     * This method is responsible for initiating the download of a file from the
     * specified file path. The implementation should handle the output of file
     * content to the client.
     *
     * @param string $filePath The path to the file that needs to be downloaded.
     * @return void
     */
    public function downloadFile(string $filePath): void;
}
