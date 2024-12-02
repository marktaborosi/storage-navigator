<?php

namespace Marktaborosi\StorageBrowser\Adapters;

use Marktaborosi\StorageBrowser\Builders\FileStructureBuilder;
use Marktaborosi\StorageBrowser\Entities\DirectoryAttribute;
use Marktaborosi\StorageBrowser\Entities\FileAttribute;
use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserAdapterInterface;
use Marktaborosi\StorageBrowser\Traits\PathHelperTrait;

/**
 * Basic filesystem adapter for browsing files and directories using native PHP functions.
 *
 * The Basic adapter class implements the FileBrowserAdapterInterface and provides
 * methods to retrieve the structure of a location (files and directories) and to
 * check if a location exists.
 *
 * @package Marktaborosi\StorageBrowser\Adapters
 * @pattern Adapter / Facade
 */
class PHPNativeAdapter implements StorageBrowserAdapterInterface
{
    use PathHelperTrait;
    /**
     * Get the structure of a given location (directory).
     *
     * This method retrieves files and directories from the specified location,
     * creates FileAttribute and DirectoryAttribute objects, and adds them to
     * a Structure object which is then returned.
     *
     * @param string $location The directory location to scan.
     * @return FileStructure The structure of files and directories in the given location.
     */
    public function getFileStructure(string $location): FileStructure
    {
        $objects = [];
        $objects['directories'] = [];
        $objects['files'] = [];

        $structureBuilder = new FileStructureBuilder();

        $entries = scandir($location);

        foreach ($entries as $file) {
            $fileLocation = $location . "/" . $file;
            // Ignore . and ..
            if ($file == ".." || $file == ".") continue;

            if (is_dir($fileLocation)) {
                $objects['directories'][] = $fileLocation;
            } else {
                $fileTime = date("U", filemtime($fileLocation));
                $objects['files'][$fileTime . "-" . $fileLocation] = $fileLocation;
            }
        }

        foreach ($objects['directories'] as $file) {
            $structureBuilder->addDirectory(
                directory: new DirectoryAttribute(
                    name: basename($file),
                    path: $this->getNormalizedDirname($file),
                    lastModified: filemtime($file)
                )
            );
        }

        foreach ($objects['files'] as $file) {
            $structureBuilder->addFile(
                file: new FileAttribute(
                    directoryPath: $this->getNormalizedDirname($file),
                    filename: basename($file),
                    extension: pathinfo($file, PATHINFO_EXTENSION),
                    byteSize: filesize($file),
                    lastModified: filemtime($file)
                )
            );
        }

        return $structureBuilder->sortBy()->build();
    }

    /**
     * Check if the specified location exists.
     *
     * This method checks if the given location exists and is a directory.
     *
     * @param string $location The directory location to check.
     * @return bool True if the location exists, false otherwise.
     */
    public function fileOrDirectoryExists(string $location): bool
    {
        return file_exists($location);
    }

    public function downloadFile(string $filePath): void
    {
        // Serve the file for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
    }
}
