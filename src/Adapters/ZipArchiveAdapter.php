<?php

namespace Marktaborosi\StorageBrowser\Adapters;

use Exception;
use Marktaborosi\StorageBrowser\Builders\FileStructureBuilder;
use Marktaborosi\StorageBrowser\Entities\DirectoryAttribute;
use Marktaborosi\StorageBrowser\Entities\FileAttribute;
use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserAdapterInterface;
use Marktaborosi\StorageBrowser\Traits\ArchiveHelperTrait;
use Marktaborosi\StorageBrowser\Traits\PathHelperTrait;
use ZipArchive;

/**
 * ZipArchive adapter for browsing files and directories within a ZIP file.
 *
 * This adapter implements the FileBrowserAdapterInterface and provides
 * methods to retrieve the structure of a ZIP archive (files and directories).
 *
 * @package Marktaborosi\StorageBrowser\Adapters
 * @pattern Adapter / Facade
 *
 */
class ZipArchiveAdapter implements StorageBrowserAdapterInterface
{
    use PathHelperTrait, ArchiveHelperTrait;

    /**
     * @var string The path to the ZIP file.
     */
    private string $zipFilePath;

    /**
     * @var ZipArchive The ZipArchive instance.
     */
    private ZipArchive $zipArchive;

    /**
     * @var array An array to store the item count for directories.
     */
    private array $directoryMetadata = [];

    /**
     * Constructor for the ZipArchiveAdapter class.
     *
     * @param string $zipFilePath The path to the ZIP file.
     * @throws Exception If the ZIP file cannot be opened.
     */
    public function __construct(string $zipFilePath)
    {
        $this->zipFilePath = $zipFilePath;
        $this->zipArchive = new ZipArchive();

        if ($this->zipArchive->open($zipFilePath) !== true) {
            throw new Exception("Unable to open ZIP file: $zipFilePath");
        }
    }

    /**
     * Get the structure of the ZIP archive.
     *
     * This method retrieves files and directories from the ZIP archive,
     * creates FileAttribute and DirectoryAttribute objects, and adds them to
     * a Structure object which is then returned.
     *
     * @param string $location The directory location within the ZIP to scan.
     * @return FileStructure The structure of files and directories in the given location.
     */
    public function getFileStructure(string $location = ''): FileStructure
    {
        $structureBuilder = new FileStructureBuilder();
        $normalizedLocation = trim($location, '/'); // Normalize location
        $maxLevel = empty($location) ? 1 : substr_count($location, "/") + 3;
        $directoryItems = [];

        // Iterate through all files in the ZIP archive
        for ($i = 0; $i < $this->zipArchive->numFiles; $i++) {
            $fileInfo = $this->zipArchive->statIndex($i);
            $filePath = $fileInfo['name'];

            // Strip the location prefix from the file path
            $relativeFilePath = $normalizedLocation ? str_replace("$normalizedLocation/", "", $filePath) : $filePath;

            // Skip files that are not in the specified location or deeper than a specific level
            if (!str_starts_with($filePath, $normalizedLocation) || empty($relativeFilePath) || $this->fileDeeperThanLevel($maxLevel, $filePath)) {
                continue;
            }

            $pathParts = explode("/", $relativeFilePath);

            // Handle files at the root of the location
            if (count($pathParts) === 1) {
                $structureBuilder->addFile(
                    file: new FileAttribute(
                        directoryPath: $normalizedLocation ? "$normalizedLocation/" : '',
                        filename: $pathParts[0],
                        extension: pathinfo($pathParts[0], PATHINFO_EXTENSION),
                        byteSize: $fileInfo['size'],
                        lastModified: $fileInfo['mtime']
                    )
                );
            } else {
                // Track directories and their item counts
                $directoryName = $pathParts[0];
                $directoryItems[$directoryName] = $fileInfo['mtime'];
            }
        }

        // Add directories to the structure
        foreach ($directoryItems as $directoryName => $lastModified) {
            $structureBuilder->addDirectory(
                directory: new DirectoryAttribute(
                    name: $directoryName,
                    path: $normalizedLocation ? "$normalizedLocation/" : '',
                    lastModified: $lastModified
                )
            );
        }

        return $structureBuilder->sortBy()->build();
    }


    /**
     * Check if the specified location exists within the ZIP archive.
     *
     * This method checks if the given location exists in the ZIP file.
     *
     * @param string $location The directory location to check.
     * @return bool True if the location exists, false otherwise.
     */
    public function fileOrDirectoryExists(string $location): bool
    {
        // Special case for root
        if ($location === '/') {
            return true;
        }

        // Check if any file path starts with the given location
        for ($i = 0; $i < $this->zipArchive->numFiles; $i++) {
            $stat = $this->zipArchive->statIndex($i);
            if (str_starts_with($stat['name'], $location)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle the downloading of a file.
     *
     * @param string $filePath The path to the file within the ZIP that needs to be downloaded.
     * @return void
     */
    public function downloadFile(string $filePath): void
    {
        $tempFilePath = sys_get_temp_dir() . '/' . basename($filePath);
        try {
            // Extract the file to a temporary location
            if ($this->zipArchive->extractTo(sys_get_temp_dir(), $filePath)) {
                // Serve the file for download
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                header('Content-Length: ' . filesize($tempFilePath));
                readfile($tempFilePath);

                // Clean up the temporary file
                unlink($tempFilePath);
            } else {
                throw new Exception("Failed to extract the file.");
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    /**
     * Destructor for the ZipArchiveAdapter class.
     *
     * Ensures that the ZIP archive is closed when the object is destroyed.
     */
    public function __destruct()
    {
        $this->zipArchive->close();
    }
}
