<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Entry\EntryType;

final readonly class TypeFilter implements FilterInterface
{
    public function __construct(private EntryType $type)
    {
    }

    public function matches(Entry $entry): bool
    {
        return $entry->type === $this->type;
    }
}
