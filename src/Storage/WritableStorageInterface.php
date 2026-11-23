<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Storage;

use Marktaborosi\StorageNavigator\Exception\StorageException;
use Marktaborosi\StorageNavigator\Path\Path;

/**
 * A storage that also supports the write features (upload, new folder, rename, delete).
 * Callers check existence and permissions before calling these methods.
 */
interface WritableStorageInterface extends StorageInterface
{
    /**
     * Writes a file from a stream, replacing an existing file.
     *
     * @param resource $stream
     *
     * @throws StorageException
     */
    public function writeStream(Path $path, $stream): void;

    /**
     * @throws StorageException
     */
    public function createDirectory(Path $path): void;

    /**
     * Moves or renames a file or directory.
     *
     * @throws StorageException
     */
    public function move(Path $from, Path $to): void;

    /**
     * Deletes a file, or a directory with everything in it.
     *
     * @throws StorageException
     */
    public function delete(Path $path): void;
}
