<?php
namespace Marktaborosi\StorageNavigator\Entities;

/**
 * Class representing a directory attribute.
 *
 * The DirectoryAttribute class stores metadata about a directory, such as its name,
 * the number of items it contains, and its total size in bytes. It also provides methods
 * to retrieve these attributes and check if the object represents a file or a directory.
 */
class DirectoryAttribute
{
    private string $name;
    private string $path;
    private ?int $lastModified;

    /**
     * Constructor for the DirectoryAttribute class.
     *
     * @param string $name The name of the directory.
     */
    public function __construct(
        string $name,
        string $path,
        ?int $lastModified
    )
    {
        $this->name = $name;
        $this->path = $path;
        $this->lastModified = $lastModified;
    }

    public function getLastModified(): ?int
    {
        return $this->lastModified;
    }

    /**
     * Get the path of the directory.
     *
     * @return string The name of the directory.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the name of the directory.
     *
     * @return string The name of the directory.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check if the object represents a directory.
     *
     * @return bool Always returns true for DirectoryAttribute as it represents a directory.
     */
    public function isDir(): bool
    {
        return true;
    }

    /**
     * Check if the object represents a file.
     *
     * @return bool Always returns false for DirectoryAttribute as it represents a directory.
     */
    public function isFile(): bool
    {
        return false;
    }
}
