<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Http;

use Psr\Http\Message\StreamInterface;

/**
 * A response body produced by a callback that writes to an output stream
 * (used for streamed ZIP downloads).
 *
 * SapiEmitter runs the callback straight into php://output, so nothing is buffered.
 * Other emitters that read the stream get the output buffered in php://temp, which
 * spills to disk above 2 MB.
 */
final class CallbackStream implements StreamInterface
{
    /** @var \Closure(resource): void|null */
    private ?\Closure $callback;

    /** @var resource|null */
    private $buffer = null;

    /**
     * @param \Closure(resource): void $callback
     */
    public function __construct(\Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Writes the whole body to $output. Can be called once.
     *
     * @param resource $output
     */
    public function writeTo($output): void
    {
        if ($this->callback === null) {
            throw new \RuntimeException('The stream has already been consumed.');
        }
        $callback = $this->callback;
        $this->callback = null;
        $callback($output);
    }

    public function __toString(): string
    {
        try {
            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        if ($this->buffer !== null) {
            fclose($this->buffer);
        }
        $this->buffer = null;
        $this->callback = null;
    }

    public function detach()
    {
        $buffer = $this->buffer();
        $this->buffer = null;

        return $buffer;
    }

    public function getSize(): ?int
    {
        return null;
    }

    public function tell(): int
    {
        return (int) ftell($this->buffer());
    }

    public function eof(): bool
    {
        return feof($this->buffer());
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        throw new \RuntimeException('Stream is not seekable.');
    }

    public function rewind(): void
    {
        throw new \RuntimeException('Stream is not seekable.');
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
        return true;
    }

    public function read(int $length): string
    {
        $data = fread($this->buffer(), max(1, $length));

        return $data === false ? '' : $data;
    }

    public function getContents(): string
    {
        $data = stream_get_contents($this->buffer());

        return $data === false ? '' : $data;
    }

    public function getMetadata(?string $key = null)
    {
        return $key === null ? [] : null;
    }

    /**
     * @return resource
     */
    private function buffer()
    {
        if ($this->buffer === null) {
            $buffer = fopen('php://temp/maxmemory:2097152', 'w+b');
            if ($buffer === false) {
                throw new \RuntimeException('Unable to open a temporary buffer.');
            }
            $this->writeTo($buffer);
            rewind($buffer);
            $this->buffer = $buffer;
        }

        return $this->buffer;
    }
}
