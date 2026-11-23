<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * The operation is disabled, not authorized, or the CSRF token is invalid.
 */
final class AccessDeniedException extends \RuntimeException implements HttpException
{
    public function statusCode(): int
    {
        return 403;
    }
}
