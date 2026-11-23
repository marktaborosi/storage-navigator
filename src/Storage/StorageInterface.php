<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Storage;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Marktaborosi\StorageNavigator\Exception\StorageException;
use Marktaborosi\StorageNavigator\Path\Path;

/**
 * Read access to a storage. Paths are always relative to the storage root and
 * already validated (see Path), so implementations never see "..".
 *
 * Implementations must not apply visibility rules (filters, hidden files);
 * the navigator does that on top of every storage.
 */
interface StorageInterface
{
    /**
     * Metadata of a file or directory, or null when nothing exists at the path.
     * The root always exists and is a directory.
     *
     * @throws StorageException
     */
    public function stat(Path $path): ?Entry;

    /**
     * Direct children of a directory, in no particular order.
     *
     * @return iterable<Entry>
     *
     * @throws NotFoundException when the directory does not exist
     * @throws StorageException
     */
    public function list(Path $directory): iterable;

    /**
     * Every file and directory below a directory, at any depth.
     *
     * @return iterable<Entry>
     *
     * @throws NotFoundException
     * @throws StorageException
     */
    public function listRecursive(Path $directory): iterable;

    /**
     * Opens a file for reading.
     *
     * @return resource
     *
     * @throws NotFoundException
     * @throws StorageException
     */
    public function readStream(Path $path);
}
