<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;

/**
 * Matches dotfiles and dot-directories (".env", ".git").
 */
final readonly class HiddenFilter implements FilterInterface
{
    public function matches(Entry $entry): bool
    {
        return $entry->isHidden();
    }
}
