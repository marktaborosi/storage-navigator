<?php

namespace Marktaborosi\StorageBrowser\Structure;

use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\Entities\FileAttribute;
use Marktaborosi\StorageBrowser\Entities\FileStructure;

/**
 * Class FileStructureFilterer
 *
 * Provides functionality to filter a `FileStructure` based on configuration settings.
 * It filters out files and directories based on ignore patterns for filenames and file extensions.
 *
 * @package Marktaborosi\StorageBrowser\Structure
 * @pattern Strategy
 */
class FileStructureFilterer
{
    /**
     * Filter the entries of a FileStructure based on the provided FileBrowserConfig.
     *
     * This method filters out entries from the `FileStructure` based on the ignore settings for filenames and file extensions
     * specified in the `FileBrowserConfig`. Only entries that do not match the ignore patterns are retained.
     *
     * @param FileStructure $structure The `FileStructure` instance to be filtered.
     * @param FileBrowserConfig $config The configuration containing ignore settings.
     * @return FileStructure A new `FileStructure` instance containing only the entries that were not filtered out.
     */
    public static function filter(FileStructure $structure, FileBrowserConfig $config): FileStructure
    {
        $filteredList = [];

        foreach ($structure->getEntries() as $entry) {
            // Check if the entry is a file or directory
            $isFile = $entry instanceof FileAttribute;
            $entryName = $isFile ? $entry->getFilename() : $entry->getName();

            // Get ignore settings from the configuration
            $ignoreExtensions = $config->get("ignore_extensions");
            $ignoreFilenames = $config->get("ignore_filenames");

            $match = false;

            // Check if the file extension is in the ignore list
            if ($isFile && $ignoreExtensions && in_array(strtolower($entry->getExtension()), $ignoreExtensions)) {
                $match = true;
            }

            // Check if the filename is in the ignore list
            if ($ignoreFilenames && in_array($entryName, $ignoreFilenames)) {
                $match = true;
            }

            // Add entry to the filtered list if it does not match the ignore criteria
            if (!$match) {
                $filteredList[] = $entry;
            }
        }

        // Return a new FileStructure instance with the filtered entries
        return new FileStructure($filteredList);
    }
}
