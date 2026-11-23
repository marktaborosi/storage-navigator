<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * The request is missing a parameter or carries an invalid one.
 */
final class BadRequestException extends \RuntimeException implements HttpException
{
    public function statusCode(): int
    {
        return 400;
    }
}
