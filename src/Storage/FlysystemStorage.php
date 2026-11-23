<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Storage;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use League\MimeTypeDetection\MimeTypeDetector;
use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Exception\InvalidPathException;
use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Marktaborosi\StorageNavigator\Exception\StorageException;
use Marktaborosi\StorageNavigator\Path\Path;

/**
 * Any Flysystem v3 filesystem: local disk, S3, FTP, SFTP, ZIP, in-memory and every
 * community adapter.
 *
 * The optional $root narrows the browsable area to a sub-directory of the filesystem.
 * Flysystem itself rejects path traversal, and Path never produces "..", so nothing
 * outside $root is reachable.
 */
final class FlysystemStorage implements WritableStorageInterface
{
    private readonly string $root;
    private readonly MimeTypeDetector $mimeTypes;

    /**
     * @param \Closure(Path): void|null $guard called before every access; throws to deny it
     *                                         (used by Storage::local() to stop symlinks leading outside)
     */
    public function __construct(
        private readonly FilesystemOperator $filesystem,
        string $root = '',
        ?MimeTypeDetector $mimeTypes = null,
        private readonly ?\Closure $guard = null,
    ) {
        $this->root = Path::fromString($root)->toString();
        $this->mimeTypes = $mimeTypes ?? new ExtensionMimeTypeDetector();
    }

    public function stat(Path $path): ?Entry
    {
        if ($path->isRoot()) {
            return Entry::directory($path);
        }
        $this->guard($path);
        $location = $this->location($path);

        try {
            if ($this->filesystem->fileExists($location)) {
                return Entry::file(
                    $path,
                    $this->attempt(fn (): int => $this->filesystem->fileSize($location)),
                    $this->attempt(fn (): int => $this->filesystem->lastModified($location)),
                    $this->mimeTypes->detectMimeTypeFromPath($location),
                );
            }
            if ($this->filesystem->directoryExists($location)) {
                return Entry::directory($path, $this->attempt(fn (): int => $this->filesystem->lastModified($location)));
            }
        } catch (FilesystemException $e) {
            throw StorageException::wrap('stat', $location, $e);
        }

        return null;
    }

    public function list(Path $directory): iterable
    {
        return $this->listContents($directory, false);
    }

    public function listRecursive(Path $directory): iterable
    {
        return $this->listContents($directory, true);
    }

    public function readStream(Path $path)
    {
        $this->guard($path);
        $location = $this->location($path);

        try {
            if (!$this->filesystem->fileExists($location)) {
                throw new NotFoundException('File not found.');
            }

            return $this->filesystem->readStream($location);
        } catch (FilesystemException $e) {
            throw StorageException::wrap('read', $location, $e);
        }
    }

    public function writeStream(Path $path, $stream): void
    {
        $this->guard($path);
        $this->run('write', $path, fn () => $this->filesystem->writeStream($this->location($path), $stream));
    }

    public function createDirectory(Path $path): void
    {
        $this->guard($path);
        $this->run('createDirectory', $path, fn () => $this->filesystem->createDirectory($this->location($path)));
    }

    public function move(Path $from, Path $to): void
    {
        $this->guard($from);
        $this->guard($to);
        $source = $this->location($from);
        $target = $this->location($to);
        $this->run('move', $from, function () use ($source, $target): void {
            if ($this->filesystem->fileExists($source)) {
                $this->filesystem->move($source, $target);

                return;
            }
            // Not every adapter can move directories (S3, in-memory): move the contents one by one.
            try {
                $this->filesystem->move($source, $target);

                return;
            } catch (FilesystemException) {
            }
            $this->filesystem->createDirectory($target);
            /** @var StorageAttributes $item */
            foreach ($this->filesystem->listContents($source, true)->sortByPath() as $item) {
                $destination = $target . substr($item->path(), strlen($source));
                if ($item->isDir()) {
                    $this->filesystem->createDirectory($destination);
                } else {
                    $this->filesystem->move($item->path(), $destination);
                }
            }
            $this->filesystem->deleteDirectory($source);
        });
    }

    public function delete(Path $path): void
    {
        if ($path->isRoot()) {
            throw new \LogicException('The root directory cannot be deleted.');
        }
        $this->guard($path);
        $location = $this->location($path);
        $this->run('delete', $path, function () use ($location): void {
            if ($this->filesystem->fileExists($location)) {
                $this->filesystem->delete($location);
            } else {
                $this->filesystem->deleteDirectory($location);
            }
        });
    }

    /**
     * @return \Generator<Entry>
     */
    private function listContents(Path $directory, bool $deep): \Generator
    {
        $this->guard($directory);
        $location = $this->location($directory);

        try {
            if (!$directory->isRoot() && !$this->filesystem->directoryExists($location)) {
                throw new NotFoundException('Directory not found.');
            }
            /** @var StorageAttributes $item */
            foreach ($this->filesystem->listContents($location, $deep) as $item) {
                $entry = $this->toEntry($item);
                if ($entry !== null) {
                    yield $entry;
                }
            }
        } catch (FilesystemException $e) {
            throw StorageException::wrap('list', $location, $e);
        }
    }

    private function toEntry(StorageAttributes $item): ?Entry
    {
        $relative = $this->root === '' ? $item->path() : substr($item->path(), strlen($this->root) + 1);

        try {
            $path = Path::fromString($relative);
        } catch (InvalidPathException) {
            return null; // a stored name we cannot represent safely (control characters, invalid UTF-8)
        }
        if ($path->isRoot()) {
            return null;
        }

        if ($item instanceof FileAttributes) {
            return Entry::file(
                $path,
                $item->fileSize(),
                $item->lastModified(),
                $this->mimeTypes->detectMimeTypeFromPath($item->path()),
            );
        }
        if ($item instanceof DirectoryAttributes) {
            return Entry::directory($path, $item->lastModified());
        }

        return null;
    }

    private function location(Path $path): string
    {
        $relative = $path->toString();

        return match (true) {
            $this->root === '' => $relative,
            $relative === '' => $this->root,
            default => $this->root . '/' . $relative,
        };
    }

    private function guard(Path $path): void
    {
        if ($this->guard !== null) {
            ($this->guard)($path);
        }
    }

    /**
     * Optional metadata: some adapters cannot provide it, which must not break the listing.
     *
     * @param \Closure(): int $read
     */
    private function attempt(\Closure $read): ?int
    {
        try {
            return $read();
        } catch (FilesystemException) {
            return null;
        }
    }

    private function run(string $operation, Path $path, \Closure $callback): void
    {
        try {
            $callback();
        } catch (FilesystemException $e) {
            throw StorageException::wrap($operation, $path->toString(), $e);
        }
    }
}
