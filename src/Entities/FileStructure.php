<?php
namespace Marktaborosi\StorageNavigator\Entities;

/**
 * Represents the structure of files and directories.
 *
 * The Structure class is responsible for managing a collection of FileAttribute and DirectoryAttribute
 * objects, and provides methods to retrieve and format the structure as an array.
 */
class FileStructure
{
    /**
     * @var DirectoryAttribute[]|FileAttribute[]
     */
    private array $entries;

    /**
     * @param FileAttribute[]|DirectoryAttribute[] $items
     */
    public function __construct(array $items)
    {
        $this->entries = $items;
    }

    /**
     * Convert the structure to an array format.
     *
     * The method returns an array representation of the structure,
     * including details about files and directories such as name, size,
     * last modified date, extension, and items count.
     *
     * @return array An array representing the structure of files and directories.
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->entries as $entry) {
            if ($entry instanceof FileAttribute) {
                $array[] = [
                    "type" => "file",
                    "filename" => $entry->getFilename(),
                    "directory_path" => $entry->getDirectoryPath(),
                    "size" => $this->size($entry->getByteSize()),
                    "last_modified" => $entry->getLastModified(),
                    "extension" => $entry->getExtension()
                ];
            }
            if ($entry instanceof DirectoryAttribute) {
                $array[] = [
                    "type" => "dir",
                    "name" => $entry->getName(),
                    "path" => $entry->getPath(),
                    'last_modified' => $entry->getLastModified()
                ];
            }
        }

        return $array;
    }

    /**
     * Get all items in the structure.
     *
     * @return array An array of all items (files and directories) in the structure.
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * Get all files in the structure.
     *
     * @return FileAttribute[] An array of all files in the structure.
     */
    public function getFiles(): array
    {
        return array_filter($this->entries, function(FileAttribute|DirectoryAttribute $item) {
            return $item->isFile();
        });
    }

    /**
     * Get all directories in the structure.
     *
     * @return DirectoryAttribute[] An array of all directories in the structure.
     */
    public function getDirectories(): array
    {
        return array_filter($this->entries, function(FileAttribute|DirectoryAttribute $item) {
            return $item->isDir();
        });
    }

    /**
     * Format the size of a file or directory in human-readable form.
     *
     * This method converts the size in bytes to a more readable format,
     * such as KB, MB, GB, etc., with a specified precision.
     *
     * @param int $bytes The size in bytes.
     * @return array An associative array containing the formatted size ('value') and unit ('unit').
     */
    private function size(int $bytes): array
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return [
            'value' => round($bytes, 2),
            'unit' => $units[$pow]
        ];
    }
}
