<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Entry;

enum SortDirection: string
{
    case Ascending = 'asc';
    case Descending = 'desc';

    public function opposite(): self
    {
        return $this === self::Ascending ? self::Descending : self::Ascending;
    }
}
