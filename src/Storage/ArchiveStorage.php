<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Storage;

use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Exception\InvalidPathException;
use Marktaborosi\StorageNavigator\Exception\MissingDependencyException;
use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Marktaborosi\StorageNavigator\Exception\StorageException;
use Marktaborosi\StorageNavigator\Path\Path;
use wapmorgan\UnifiedArchive\UnifiedArchive;

/**
 * Read-only view of an archive (RAR, 7z, TAR, ZIP and everything else
 * wapmorgan/unified-archive supports with the installed extensions).
 *
 * The archive index is built once, on first use. Directories that only exist
 * implicitly ("a/b/file.txt" without an "a/" entry) are listed too.
 * Nothing is ever extracted to disk: files are streamed straight from the archive.
 */
final class ArchiveStorage implements StorageInterface
{
    private ?UnifiedArchive $archive = null;

    /** @var array<string, Entry>|null path => entry, root excluded */
    private ?array $entries = null;

    /** @var array<string, string> path => name inside the archive */
    private array $archiveNames = [];

    public function __construct(private readonly string $archivePath)
    {
        if (!class_exists(UnifiedArchive::class)) {
            throw MissingDependencyException::forPackage('wapmorgan/unified-archive', 'ArchiveStorage');
        }
    }

    public function stat(Path $path): ?Entry
    {
        if ($path->isRoot()) {
            return Entry::directory($path);
        }

        return $this->index()[$path->toString()] ?? null;
    }

    public function list(Path $directory): iterable
    {
        $this->assertDirectory($directory);
        foreach ($this->index() as $entry) {
            if ($entry->path->parent()->equals($directory)) {
                yield $entry;
            }
        }
    }

    public function listRecursive(Path $directory): iterable
    {
        $this->assertDirectory($directory);
        foreach ($this->index() as $entry) {
            if ($entry->path->isDescendantOf($directory)) {
                yield $entry;
            }
        }
    }

    public function readStream(Path $path)
    {
        $entry = $this->stat($path);
        if ($entry === null || !$entry->isFile()) {
            throw new NotFoundException('File not found.');
        }

        try {
            $stream = $this->archive()->getFileStream($this->archiveNames[$path->toString()]);
        } catch (\Throwable $e) {
            throw StorageException::wrap('read', $path->toString(), $e);
        }
        if (!is_resource($stream)) {
            throw new StorageException(sprintf('Unable to read "%s" from the archive.', $path));
        }

        return $stream;
    }

    private function assertDirectory(Path $directory): void
    {
        if (!$directory->isRoot() && !($this->stat($directory)?->isDirectory() ?? false)) {
            throw new NotFoundException('Directory not found.');
        }
    }

    private function archive(): UnifiedArchive
    {
        if ($this->archive === null) {
            try {
                $archive = UnifiedArchive::open($this->archivePath);
            } catch (\Throwable $e) {
                throw StorageException::wrap('open', $this->archivePath, $e);
            }
            if (!$archive instanceof UnifiedArchive) {
                throw new StorageException(sprintf('Unable to open archive "%s" (unsupported format or missing PHP extension).', $this->archivePath));
            }
            $this->archive = $archive;
        }

        return $this->archive;
    }

    /**
     * @return array<string, Entry>
     */
    private function index(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        $archive = $this->archive();
        $mimeTypes = new ExtensionMimeTypeDetector();
        $entries = [];

        try {
            $names = $archive->getFiles();
        } catch (\Throwable $e) {
            throw StorageException::wrap('list', $this->archivePath, $e);
        }

        foreach ($names as $name) {
            if (!is_string($name)) {
                continue;
            }
            try {
                $path = Path::fromString($name);
            } catch (InvalidPathException) {
                continue; // unsafe name inside the archive ("../evil"), never exposed
            }
            if ($path->isRoot()) {
                continue;
            }
            foreach ($path->ancestors() as $ancestor) {
                if (!$ancestor->isRoot()) {
                    $entries[$ancestor->toString()] ??= Entry::directory($ancestor);
                }
            }
            if (str_ends_with($name, '/')) {
                $entries[$path->toString()] ??= Entry::directory($path);
                continue;
            }

            try {
                $data = $archive->getFileData($name);
            } catch (\Throwable) {
                continue;
            }
            $entries[$path->toString()] = Entry::file(
                $path,
                (int) $data->uncompressedSize,
                $data->modificationTime > 0 ? (int) $data->modificationTime : null,
                $mimeTypes->detectMimeTypeFromPath($path->toString()),
            );
            $this->archiveNames[$path->toString()] = $name;
        }

        return $this->entries = $entries;
    }
}
