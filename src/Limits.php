<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator;

use Marktaborosi\StorageNavigator\Exception\ConfigurationException;

/**
 * Size and count limits, in bytes where not stated otherwise.
 *
 * The upload limit is checked by the navigator; PHP's own upload_max_filesize and
 * post_max_size still apply on top of it.
 */
final readonly class Limits
{
    public const int MIB = 1024 * 1024;

    public function __construct(
        public int $maxUploadBytes = 64 * self::MIB,
        public int $maxZipBytes = 1024 * self::MIB,
        public int $maxZipEntries = 10_000,
        public int $maxTextPreviewBytes = 2 * self::MIB,
    ) {
        foreach (get_object_vars($this) as $name => $value) {
            if ($value < 1) {
                throw new ConfigurationException(sprintf('Limits::$%s must be a positive integer.', $name));
            }
        }
    }
}
