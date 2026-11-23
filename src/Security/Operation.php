<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Security;

/**
 * Everything a client can ask the navigator to do. Passed to the authorization callback.
 */
enum Operation: string
{
    case List = 'list';
    case Download = 'download';
    case Preview = 'preview';
    case ZipDownload = 'zip';
    case Upload = 'upload';
    case CreateDirectory = 'mkdir';
    case Rename = 'rename';
    case Delete = 'delete';

    public function isWrite(): bool
    {
        return match ($this) {
            self::Upload, self::CreateDirectory, self::Rename, self::Delete => true,
            default => false,
        };
    }
}
