<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * The requested path or name is malformed (".." segments, control characters, invalid UTF-8, too long).
 */
final class InvalidPathException extends \InvalidArgumentException implements HttpException
{
    public function statusCode(): int
    {
        return 400;
    }
}
