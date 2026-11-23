<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator;

/**
 * Which operations the navigator offers. Disabled operations are rejected on the
 * server, not only hidden in the UI.
 *
 * Read features are on by default; every write feature is off. Write features need a
 * writable storage and Options::$csrfSecret.
 */
final readonly class Features
{
    public function __construct(
        public bool $navigation = true,
        public bool $download = true,
        public bool $preview = true,
        public bool $zipDownload = false,
        public bool $upload = false,
        public bool $createDirectory = false,
        public bool $rename = false,
        public bool $delete = false,
    ) {
    }

    /**
     * Browsing, preview and download; no writes.
     */
    public static function readOnly(bool $zipDownload = false): self
    {
        return new self(zipDownload: $zipDownload);
    }

    /**
     * Everything, including folder downloads and all write operations.
     */
    public static function all(): self
    {
        return new self(true, true, true, true, true, true, true, true);
    }

    public function hasWriteFeature(): bool
    {
        return $this->upload || $this->createDirectory || $this->rename || $this->delete;
    }
}
