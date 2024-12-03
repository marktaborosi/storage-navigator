<?php

namespace Marktaborosi\StorageBrowser\Adapters;

use Exception;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Marktaborosi\StorageBrowser\Builders\FileStructureBuilder;
use Marktaborosi\StorageBrowser\Entities\DirectoryAttribute;
use Marktaborosi\StorageBrowser\Entities\FileAttribute;
use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserAdapterInterface;
use Marktaborosi\StorageBrowser\Traits\PathHelperTrait;

/**
 * Class FilesystemAdapter
 *
 * Adapter for integrating with League Flysystem to provide file and directory browsing functionalities.
 * This class implements the StorageBrowserAdapterInterface, adapting Flysystem's API to the interface expected by
 * the file browser system.
 *
 * @package Marktaborosi\StorageBrowser\Adapters
 * @pattern Adapter / Facade
 */
class FlysystemAdapter implements StorageBrowserAdapterInterface
{
    use PathHelperTrait;

    /**
     * @var FilesystemOperator The Flysystem instance.
     */
    private FilesystemOperator $filesystem;

    /**
     * FilesystemAdapter constructor.
     *
     * @param FilesystemOperator $filesystem The Flysystem instance to be used for file operations.
     */
    public function __construct(FilesystemOperator $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Get the structure of a given location (directory).
     *
     * Retrieves files and directories from the specified location, creates FileAttribute and DirectoryAttribute
     * objects, and adds them to a FileStructure object which is then returned.
     *
     * @param string $location The directory location to scan.
     * @return FileStructure The structure of files and directories in the given location.
     * @throws FilesystemException If an error occurs while interacting with Flysystem.
     */
    public function getFileStructure(string $location): FileStructure
    {
        $structureBuilder = new FileStructureBuilder();

        // List the contents of the directory
        $contents = $this->filesystem->listContents($location, false);

        foreach ($contents as $file) {
            if ($file->isDir()) {
                // Add directory metadata to structure
                $structureBuilder->addDirectory(
                    new DirectoryAttribute(
                        name: basename($file->path()),
                        path: $this->getNormalizedDirname($file->path()),
                        lastModified: $file->lastModified()
                    )
                );
            } elseif ($file->isFile()) {
                // Get file metadata
                $fileSize = $this->filesystem->fileSize($file->path());

                $structureBuilder->addFile(
                    new FileAttribute(
                        directoryPath: $this->getNormalizedDirname($file->path()),
                        filename: basename($file->path()),
                        extension: pathinfo($file->path(), PATHINFO_EXTENSION),
                        byteSize: $fileSize,
                        lastModified: $file->lastModified()
                    )
                );
            }
        }

        return $structureBuilder->sortBy()->build();
    }

    /**
     * Check if the specified location exists.
     *
     * Checks if the given location exists and is either a directory or a file.
     *
     * @param string $location The directory location to check.
     * @return bool True if the location exists and is a directory or file, false otherwise.
     * @throws FilesystemException If an error occurs while interacting with Flysystem.
     */
    public function fileOrDirectoryExists(string $location): bool
    {
        return $this->filesystem->directoryExists($location) || $this->filesystem->fileExists($location);
    }

    /**
     * Handle the downloading of a file.
     *
     * Outputs the file content to the client with appropriate headers for downloading.
     *
     * @param string $filePath The path to the file that needs to be downloaded.
     * @return void
     * @throws FilesystemException
     * @throws Exception
     */
    public function downloadFile(string $filePath): void
    {
            // Check if the file exists
            if (!$this->filesystem->fileExists($filePath)) {
                throw new Exception("File does not exist.");
            }

            // Get the file's MIME type
            $mimeType = $this->filesystem->mimeType($filePath);

            // Get the file's size
            $fileSize = $this->filesystem->fileSize($filePath);

            // Get the file stream
            $stream = $this->filesystem->readStream($filePath);

            if (!$stream) {
                throw new Exception("Unable to read the file.");
            }

            // Set headers for the file download
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . $fileSize);

            // Output the file content
            fpassthru($stream);

            // Close the stream
            fclose($stream);
    }
}
