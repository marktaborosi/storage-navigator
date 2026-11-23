<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * The Range header cannot be satisfied for this file.
 */
final class RangeNotSatisfiableException extends \RuntimeException implements HttpException
{
    public function statusCode(): int
    {
        return 416;
    }
}
