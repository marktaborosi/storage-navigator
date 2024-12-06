<?php
namespace Marktaborosi\StorageNavigator\Entities;

/**
 * Class representing a file attribute.
 *
 * The FileAttribute class stores metadata about a file, such as its name, extension,
 * size in bytes, and last modified date. It also provides methods to retrieve these
 * attributes and check if the object represents a file or a directory.
 */
class FileAttribute
{
    /**
     * @var string The name of the filepath.
     */
    private string $directoryPath;

    /**
     * @var string The name of the filename.
     */
    private string $filename;

    /**
     * @var string The file extension.
     */
    private string $extension;

    /**
     * @var int The size of the file in bytes.
     */
    private int $byteSize;

    /**
     * @var string|null The last modified date of the file.
     */
    private ?string $lastModified;

    /**
     * Constructor for the FileAttribute class.
     *
     * @param string $directoryPath The name of the file.
     * @param string $extension The file extension.
     * @param int $byteSize The size of the file in bytes.
     * @param ?string $lastModified The last modified date of the file.
     */
    public function __construct(
        string $directoryPath,
        string $filename,
        string $extension,
        int    $byteSize,
        ?string $lastModified
    )
    {
        $this->directoryPath = $directoryPath;
        $this->filename = $filename;
        $this->extension = $extension;
        $this->byteSize = $byteSize;
        $this->lastModified = $lastModified;
    }

    /**
     * Get the filepath
     *
     * @return string The name of the filepath.
     */
    public function getDirectoryPath(): string
    {
        return $this->directoryPath;
    }

    /**
     * Get the filename.
     *
     * @return string The name of the file.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * Get the file extension.
     *
     * @return string The file extension.
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Get the size of the file in bytes.
     *
     * @return int The size of the file in bytes.
     */
    public function getByteSize(): int
    {
        return $this->byteSize;
    }

    /**
     * Get the last modified date of the file.
     *
     * @return ?string The last modified date of the file.
     */
    public function getLastModified(): ?string
    {
        return $this->lastModified;
    }

    /**
     * Check if the object represents a directory.
     *
     * @return bool Always returns false for FileAttribute as it represents a file.
     */
    public function isDir(): bool
    {
        return false;
    }

    /**
     * Check if the object represents a file.
     *
     * @return bool Always returns true for FileAttribute as it represents a file.
     */
    public function isFile(): bool
    {
        return true;
    }
}
