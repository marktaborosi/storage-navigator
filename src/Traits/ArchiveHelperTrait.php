<?php

namespace Marktaborosi\StorageBrowser\Traits;

/**
 * Trait ArchiveHelperTrait
 *
 * Provides helper methods for working with file paths in an archive context.
 */
trait ArchiveHelperTrait
{
    /**
     * Determines if a given file path is deeper than a specified directory level.
     *
     * This method checks if the file path contains more directory levels than the specified level.
     * It does this by counting the number of slashes (`/`) in the file path and comparing it to the specified level.
     * It also ensures that there is content beyond the second occurrence of the slash.
     *
     * @param int $level The directory level to compare against. For example, a level of 2 means the file path should be deeper than the second directory.
     * @param string $filePath The file path to check.
     * @return bool Returns `true` if the file path is deeper than the specified level, `false` otherwise.
     */
    public function fileDeeperThanLevel(int $level, string $filePath): bool
    {
        // Count the number of slashes in the file path
        $slashCount = substr_count($filePath, '/');

        // Check if the number of slashes is at least as many as the specified level
        if ($slashCount >= $level) {
            // Find the position of the first and second occurrence of '/'
            $firstSlashPos = strpos($filePath, '/');
            $secondSlashPos = strpos($filePath, '/', $firstSlashPos + 1);

            // Check if there is more string after the second '/'
            if ($secondSlashPos !== false && strlen($filePath) > $secondSlashPos + 1) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
