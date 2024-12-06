<?php

namespace Marktaborosi\StorageNavigator\Builders;

use Marktaborosi\StorageNavigator\Entities\DirectoryAttribute;
use Marktaborosi\StorageNavigator\Entities\FileAttribute;
use Marktaborosi\StorageNavigator\Entities\FileStructure;

/**
 * Class FileStructureBuilder
 *
 * A builder class for constructing instances of FileStructure with various files and directories.
 * This class follows the **Builder** pattern, allowing for step-by-step construction of a FileStructure object
 * with optional files and directories. The builder provides methods to add items, sort them, and finally
 * build the FileStructure instance.
 *
 * @package Marktaborosi\StorageBrowser\Builders
 * @pattern Builder
 */
class FileStructureBuilder
{
    /**
     * An array of items representing files and directories.
     *
     * @var FileAttribute[]|DirectoryAttribute[]
     */
    private array $items = [];

    /**
     * Adds a file to the structure.
     *
     * @param FileAttribute $file The file to add to the structure.
     * @return self Returns the instance of the builder for method chaining.
     */
    public function addFile(FileAttribute $file): self
    {
        $this->items[] = $file;
        return $this;
    }

    /**
     * Adds a directory to the structure.
     *
     * @param DirectoryAttribute $directory The directory to add to the structure.
     * @return self Returns the instance of the builder for method chaining.
     */
    public function addDirectory(DirectoryAttribute $directory): self
    {
        $this->items[] = $directory;
        return $this;
    }

    /**
     * Sorts the items in the structure. Directories are sorted first by name (A-Z),
     * followed by files, which are sorted by filename (A-Z).
     *
     * @return self Returns the instance of the builder for method chaining.
     */
    public function sortByAZ(): self
    {
        // Separate directories and files
        $directories = [];
        $files = [];

        foreach ($this->items as $item) {
            if ($item instanceof DirectoryAttribute) {
                $directories[] = $item;
            } elseif ($item instanceof FileAttribute) {
                $files[] = $item;
            }
        }

        // Sort directories by name (A-Z)
        usort($directories, function($a, $b) {
            return strcmp($a->getName(), $b->getName());
        });

        // Sort files by filename (A-Z)
        usort($files, function($a, $b) {
            return strcmp($a->getFilename(), $b->getFilename());
        });

        // Merge sorted directories and files back into one array
        $this->items = array_merge($directories, $files);

        return $this;
    }

    /**
     * Builds and returns the file structure from the added items.
     *
     * @return FileStructure Returns the built file structure.
     */
    public function build(): FileStructure
    {
        return new FileStructure($this->items);
    }
}
