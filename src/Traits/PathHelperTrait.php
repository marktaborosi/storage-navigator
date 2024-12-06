<?php

namespace Marktaborosi\StorageNavigator\Traits;

/**
 * Trait PathHelperTrait
 *
 * Provides helper methods for handling and normalizing file paths.
 */
trait PathHelperTrait
{
    /**
     * Normalizes a directory path by converting backslashes to forward slashes,
     * ensuring it ends with a trailing slash, and handling special cases.
     *
     * @param string $path The file or directory path to normalize.
     *
     * @return string The normalized directory path, with a trailing slash.
     */
    public function getNormalizedDirname(string $path): string
    {
        // Replace backslashes with forward slashes to normalize the path
        $normalizedPath = str_replace('\\', '/', $path);

        // Get the directory name using dirname()
        $directory = dirname($normalizedPath);

        // Ensure the directory ends with a trailing slash
        if (!str_ends_with($directory, '/')) {
            $directory .= '/';
        }

        if ($directory === './') {
            $directory = "";
        }

        return $directory;
    }

    /**
     * Checks if a given string represents a file path.
     * It detects both Unix-style (forward slashes) and Windows-style (backslashes) paths.
     *
     * @param string $string The string to check.
     *
     * @return bool True if the string contains path delimiters, false otherwise.
     */
    public function isPath(string $string): bool
    {
        // Check for Unix-style paths and Windows-style paths
        return str_contains($string, '/') || str_contains($string, '\\');
    }
}
