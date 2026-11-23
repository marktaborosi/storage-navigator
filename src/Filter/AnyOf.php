<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;

/**
 * Matches when at least one inner filter matches (logical OR). An empty list matches nothing.
 */
final readonly class AnyOf implements FilterInterface
{
    /** @var list<FilterInterface> */
    private array $filters;

    public function __construct(FilterInterface ...$filters)
    {
        $this->filters = array_values($filters);
    }

    public function matches(Entry $entry): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->matches($entry)) {
                return true;
            }
        }

        return false;
    }
}
