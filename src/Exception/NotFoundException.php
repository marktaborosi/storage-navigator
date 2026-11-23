<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * The requested file or directory does not exist or is not visible.
 */
final class NotFoundException extends \RuntimeException implements HttpException
{
    public function statusCode(): int
    {
        return 404;
    }
}
