<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;

final readonly class Not implements FilterInterface
{
    public function __construct(private FilterInterface $filter)
    {
    }

    public function matches(Entry $entry): bool
    {
        return !$this->filter->matches($entry);
    }
}
