<?php

namespace Marktaborosi\Tests\Traits;

/**
 * Trait StorageTrait
 *
 * A utility trait for handling file and directory creation, deletion, and path resolution.
 */
trait StorageTrait
{
    /**
     * The root storage location used for file and directory operations.
     */
    const ROOT_STORAGE_LOCATION = __DIR__. DIRECTORY_SEPARATOR."..". DIRECTORY_SEPARATOR. 'Storage'. DIRECTORY_SEPARATOR;

    /**
     * Get the absolute path to the root storage directory.
     *
     * @return string The absolute path to the root storage directory.
     */
    public static function rootStoragePath(): string
    {
        return realpath(self::ROOT_STORAGE_LOCATION) . DIRECTORY_SEPARATOR;
    }

    /**
     * Create multiple files at the specified paths with the provided content.
     *
     * @param array ...$filePaths A variable number of arrays where each array contains a file path and its content.
     *                            Example: ['path/to/file.txt', 'file content']
     *
     * @return array The full file paths of the created files.
     */
    public static function createFiles(...$filePaths): array
    {
        $createdFilePaths = [];

        foreach ($filePaths as $fileAttr) {
            $fullFilePath = self::rootStoragePath() . $fileAttr[0];

            // Extract the directory path
            $directoryPath = dirname($fullFilePath);

            // Check if the directory exists, if not, create it
            if (!is_dir($directoryPath)) {
                mkdir($directoryPath, 0777, true);
            }

            // Write the file contents
            file_put_contents($fullFilePath, $fileAttr[1]);

            // Add the full file path to the result array
            $createdFilePaths[] = $fullFilePath;
        }

        return $createdFilePaths;
    }

    /**
     * Create multiple directories at the specified paths.
     *
     * @param string ...$directories
     *
     * @return string[] The full paths of the created directories.
     */
    public static function createDirectories(string ...$directories): array
    {
        $createdDirectories = [];
        foreach ($directories as $directoryName) {
            $fullDirPath = self::rootStoragePath() . $directoryName;
            mkdir($fullDirPath);
            $createdDirectories[] = $fullDirPath;
        }
        return $createdDirectories;
    }

    /**
     * Delete multiple files at the specified paths.
     *
     * @param array $files An array of file paths to delete.
     *
     * @return void
     */
    public static function deleteFiles(array $files): void
    {
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Delete multiple directories at the specified paths.
     *
     * @param array $directories An array of directory paths to delete.
     *
     * @return void
     */
    public static function deleteDirectories(array $directories): void
    {
        foreach ($directories as $directory) {
            rmdir($directory);
        }
    }
}
