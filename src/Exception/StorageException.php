<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * The underlying storage failed (network error, permission problem, corrupt archive).
 * The message is meant for logs; clients only ever see a generic error.
 */
final class StorageException extends \RuntimeException implements StorageNavigatorException
{
    public static function wrap(string $operation, string $path, \Throwable $previous): self
    {
        return new self(sprintf('Storage operation "%s" failed for "%s": %s', $operation, $path, $previous->getMessage()), 0, $previous);
    }
}
