<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * An upload or a folder download exceeds the configured limits.
 */
final class PayloadTooLargeException extends \RuntimeException implements HttpException
{
    public function statusCode(): int
    {
        return 413;
    }
}
