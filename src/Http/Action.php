<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Http;

/**
 * Values of the "action" request parameter.
 */
enum Action: string
{
    case List = 'list';
    case Download = 'download';
    case Raw = 'raw';
    case Zip = 'zip';
    case Asset = 'asset';
    case Upload = 'upload';
    case CreateDirectory = 'mkdir';
    case Rename = 'rename';
    case Delete = 'delete';

    public function requiresPost(): bool
    {
        return match ($this) {
            self::Upload, self::CreateDirectory, self::Rename, self::Delete => true,
            default => false,
        };
    }
}
