<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * The action was requested with the wrong HTTP method.
 */
final class MethodNotAllowedException extends \RuntimeException implements HttpException
{
    public function statusCode(): int
    {
        return 405;
    }
}
