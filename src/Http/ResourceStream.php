<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Http;

use Psr\Http\Message\StreamInterface;

/**
 * Read-only PSR-7 stream over a PHP resource, optionally limited to a byte window
 * (for Range responses). Reading never loads the whole file into memory.
 */
final class ResourceStream implements StreamInterface
{
    /** @var resource|null */
    private $resource;
    private int $position = 0;

    /**
     * @param resource $resource
     * @param int|null $length bytes to expose from $offset on; null for "until the end"
     */
    public function __construct($resource, private readonly int $offset = 0, private readonly ?int $length = null)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('A stream resource is required.');
        }
        $this->resource = $resource;
        if ($offset > 0) {
            $this->skipTo($offset);
        }
    }

    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        if ($this->resource !== null) {
            fclose($this->resource);
            $this->resource = null;
        }
    }

    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    public function getSize(): ?int
    {
        return $this->length;
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->resource === null
            || ($this->length !== null && $this->position >= $this->length)
            || feof($this->resource);
    }

    public function isSeekable(): bool
    {
        return $this->resource !== null && (bool) (stream_get_meta_data($this->resource)['seekable'] ?? false);
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if ($whence !== SEEK_SET || !$this->isSeekable() || $this->resource === null) {
            throw new \RuntimeException('Stream is not seekable.');
        }
        if (fseek($this->resource, $this->offset + $offset) !== 0) {
            throw new \RuntimeException('Unable to seek.');
        }
        $this->position = $offset;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new \RuntimeException('Stream is read-only.');
    }

    public function isReadable(): bool
    {
        return $this->resource !== null;
    }

    public function read(int $length): string
    {
        if ($this->resource === null) {
            throw new \RuntimeException('Stream is detached.');
        }
        if ($this->length !== null) {
            $length = min($length, $this->length - $this->position);
        }
        if ($length <= 0) {
            return '';
        }
        $data = fread($this->resource, $length);
        if ($data === false) {
            throw new \RuntimeException('Unable to read from stream.');
        }
        $this->position += strlen($data);

        return $data;
    }

    public function getContents(): string
    {
        $contents = '';
        while (!$this->eof()) {
            $chunk = $this->read(65536);
            if ($chunk === '') {
                break;
            }
            $contents .= $chunk;
        }

        return $contents;
    }

    public function getMetadata(?string $key = null)
    {
        if ($this->resource === null) {
            return $key === null ? [] : null;
        }
        $metadata = stream_get_meta_data($this->resource);

        return $key === null ? $metadata : ($metadata[$key] ?? null);
    }

    private function skipTo(int $offset): void
    {
        \assert($this->resource !== null);
        if ((bool) (stream_get_meta_data($this->resource)['seekable'] ?? false)) {
            if (fseek($this->resource, $offset) === 0) {
                return;
            }
        }
        // Not seekable (remote stream): read and discard.
        $remaining = $offset;
        while ($remaining > 0 && !feof($this->resource)) {
            $chunk = fread($this->resource, min(65536, $remaining));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $remaining -= strlen($chunk);
        }
    }
}
