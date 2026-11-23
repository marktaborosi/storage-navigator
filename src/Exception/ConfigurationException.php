<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Exception;

/**
 * The navigator was configured inconsistently (for example a write feature without a CSRF secret).
 * Thrown while constructing objects, never because of a client request.
 */
final class ConfigurationException extends \LogicException implements StorageNavigatorException
{
}
