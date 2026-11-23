<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;

/**
 * Matches when every inner filter matches (logical AND). An empty list matches everything.
 */
final readonly class AllOf implements FilterInterface
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
            if (!$filter->matches($entry)) {
                return false;
            }
        }

        return true;
    }
}
