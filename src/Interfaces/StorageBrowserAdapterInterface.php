<?php
namespace Marktaborosi\StorageBrowser\Interfaces;

use Marktaborosi\StorageBrowser\Entities\FileStructure;

/**
 * Interface for storage browser adapters.
 *
 * This interface defines the methods required for implementing a storage browser adapter,
 * including checking if a location exists and retrieving the structure of a location.
 *
 * @package Marktaborosi\StorageBrowser\Interfaces
 * @pattern Adapter / Facade
 */
interface StorageBrowserAdapterInterface
{
    /**
     * Check if the specified location exists.
     *
     * @param string $location The path of the location to check.
     * @return bool Returns true if the location exists, false otherwise.
     */
    public function fileOrDirectoryExists(string $location): bool;

    /**
     * Get the structure of the specified location.
     *
     * @param string $location The path of the location to retrieve the structure from.
     * @return FileStructure The structure of the location.
     */
    public function getFileStructure(string $location): FileStructure;

    /**
     * Handles the downloading of a file.
     *
     * @param string $filePath The path to the file that needs to be downloaded.
     * @return void
     */
    public function downloadFile(string $filePath): void;
}
