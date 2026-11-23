<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * The target already exists, or the operation conflicts with the current state.
 */
final class ConflictException extends \RuntimeException implements HttpException
{
    public function statusCode(): int
    {
        return 409;
    }
}
