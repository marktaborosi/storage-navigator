<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Entry;

enum SortField: string
{
    case Name = 'name';
    case Size = 'size';
    case Modified = 'modified';
    case Type = 'type';
}
