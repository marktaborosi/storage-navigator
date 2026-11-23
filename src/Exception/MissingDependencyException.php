<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * An optional Composer package needed for the requested feature is not installed.
 */
final class MissingDependencyException extends \LogicException implements StorageNavigatorException
{
    public static function forPackage(string $package, string $feature): self
    {
        return new self(sprintf(
            '%s requires the "%s" package. Install it with: composer require %s',
            $feature,
            $package,
            $package,
        ));
    }
}
