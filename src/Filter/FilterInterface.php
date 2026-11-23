<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;

/**
 * Decides whether an entry is visible.
 *
 * Filters are enforced on the server: an entry a filter rejects is not listed, and it
 * cannot be downloaded, previewed, renamed or deleted either. A rejected directory
 * hides everything below it.
 */
interface FilterInterface
{
    public function matches(Entry $entry): bool;
}
