<?php

namespace Marktaborosi\StorageBrowser\Adapters;

use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserAdapterInterface;

/**
 * Null adapter for file browsing.
 *
 * This adapter implements the FileBrowserAdapterInterface but performs no actual operations.
 * It returns empty structures and false for existence checks.
 *
 * @package Marktaborosi\StorageBrowser\Adapters
 * @pattern Adapter / Facade
 */
class NullAdapter implements StorageBrowserAdapterInterface
{
    /**
     * Get the structure of a given location (directory).
     *
     * This method returns an empty Structure object as no actual file operations are performed.
     *
     * @param string $location The directory location to scan.
     * @return FileStructure An empty structure.
     */
    public function getFileStructure(string $location): FileStructure
    {
        return new FileStructure([]); // Return an empty structure
    }

    /**
     * Check if the specified location exists.
     *
     * This method always returns false, indicating that no location exists.
     *
     * @param string $location The directory location to check.
     * @return bool Always returns false.
     */
    public function fileOrDirectoryExists(string $location): bool
    {
        return false; // Always return false as no actual file operations are performed
    }

    public function downloadFile(string $filePath): void
    {
        echo '';
    }
}
