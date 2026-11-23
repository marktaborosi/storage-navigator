<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Entry;

use Marktaborosi\StorageNavigator\Path\Path;

/**
 * One file or directory, as reported by a storage. Immutable.
 *
 * Size, modification time and MIME type are optional because not every storage
 * reports them cheaply (S3 has no directory metadata, FTP listings vary).
 */
final readonly class Entry
{
    /**
     * @param int|null    $size         size in bytes, files only
     * @param int|null    $lastModified Unix timestamp
     * @param string|null $mimeType     detected from content or extension, files only
     */
    public function __construct(
        public Path $path,
        public EntryType $type,
        public ?int $size = null,
        public ?int $lastModified = null,
        public ?string $mimeType = null,
    ) {
    }

    public static function directory(Path $path, ?int $lastModified = null): self
    {
        return new self($path, EntryType::Directory, null, $lastModified);
    }

    public static function file(Path $path, ?int $size = null, ?int $lastModified = null, ?string $mimeType = null): self
    {
        return new self($path, EntryType::File, $size, $lastModified, $mimeType);
    }

    public function name(): string
    {
        return $this->path->name();
    }

    /**
     * Lower-case extension without the dot, "" for directories and files without one.
     */
    public function extension(): string
    {
        return $this->isFile() ? $this->path->extension() : '';
    }

    public function isFile(): bool
    {
        return $this->type === EntryType::File;
    }

    public function isDirectory(): bool
    {
        return $this->type === EntryType::Directory;
    }

    public function isHidden(): bool
    {
        return str_starts_with($this->name(), '.');
    }
}
