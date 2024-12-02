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
use wapmorgan\UnifiedArchive\Exceptions\NonExistentArchiveFileException;
use wapmorgan\UnifiedArchive\UnifiedArchive;

/**
 * ArchiveAdapter for browsing and extracting files from various archive formats.
 *
 * The ArchiveAdapter class implements the StorageBrowserAdapterInterface and provides
 * methods to retrieve the structure of an archive and to extract files.
 *
 * @package Marktaborosi\StorageBrowser\Adapters
 * @pattern Adapter / Facade
 *
 */
class UnifiedArchiveAdapter implements StorageBrowserAdapterInterface
{
    use PathHelperTrait, ArchiveHelperTrait;

    private UnifiedArchive $archive;
    private string $archivePath;

    /**
     * @throws Exception
     */
    public function __construct(string $archivePath)
    {
        $this->archivePath = $archivePath;

        // Initialize the archive using the unified-archive library
        $openedArchive = UnifiedArchive::open($archivePath);

        if (!$openedArchive) {
            throw new Exception("Could not open archive: $archivePath");
        }
        $this->archive = $openedArchive;
    }

    /**
     * Get the structure of the archive.
     *
     * @param string $location The directory location inside the archive (default to root).
     * @return FileStructure The structure of files and directories in the archive.
     * @throws NonExistentArchiveFileException
     */
    public function getFileStructure(string $location = '/'): FileStructure
    {
        $structureBuilder = new FileStructureBuilder();
        $normalizedLocation = trim($location, '/'); // Normalize location
        $maxLevel = empty($location) ? 1 : substr_count($location, "/") + 3;
        $directoryItems = [];

        // Get all files in the archive
        $files = $this->archive->getFiles();

        // Iterate over each file and create the corresponding File/Directory structure
        foreach ($files as $file) {

            $fileInfo = $this->archive->getFileData($file);
            $filePath = $fileInfo->path;

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
                        byteSize: $fileInfo->uncompressedSize,
                        lastModified: $fileInfo->modificationTime
                    )
                );
            } else {
                // Track directories and their item counts
                $directoryName = $pathParts[0];
                $directoryItems[$directoryName] = $fileInfo->modificationTime;
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
     * Check if the specified file or directory exists inside the archive.
     *
     * @param string $location The file or directory location inside the archive.
     * @return bool True if the location exists, false otherwise.
     */
    public function fileOrDirectoryExists(string $location): bool
    {
        if ($location === "") {
            return true;
        }

        $files = $this->archive->getFiles();
        foreach ($files as $file) {
            if (str_starts_with($file, $location)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Extract a file from the archive.
     *
     * @param string $filePath The path of the file to extract from the archive.
     * @throws Exception
     */
    public function downloadFile(string $filePath): void
    {
        $filePath = ltrim($filePath, '/');

        if (!$this->fileOrDirectoryExists($filePath)) {
            throw new Exception("File does not exist in the archive: $filePath");
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'archive_extract_');
        $dirname = dirname($tempFile);
        $result = $this->archive->extract($filePath, $dirname);

        if (!$result) {
            throw new Exception("Failed to extract file: $filePath");
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($tempFile));
        readfile($tempFile);

        unlink($tempFile);
    }

    /**
     * Extract all files to a specified directory.
     *
     * @param string $destination The destination directory to extract files to.
     * @throws Exception
     */
    public function extractAll(string $destination): void
    {
        $result = $this->archive->extract($destination);

        if (!$result) {
            throw new Exception("Failed to extract all files to: $destination");
        }
    }
}
