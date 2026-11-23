<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Entry;

enum EntryType: string
{
    case File = 'file';
    case Directory = 'directory';
}
