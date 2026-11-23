<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Http;

use Marktaborosi\StorageNavigator\Exception\RangeNotSatisfiableException;

/**
 * A single byte range from a Range request header (RFC 9110, section 14).
 * Multi-range requests are answered with the whole file, which the RFC allows.
 */
final readonly class ByteRange
{
    private function __construct(public int $start, public int $end)
    {
    }

    public function length(): int
    {
        return $this->end - $this->start + 1;
    }

    public function contentRange(int $size): string
    {
        return sprintf('bytes %d-%d/%d', $this->start, $this->end, $size);
    }

    /**
     * @throws RangeNotSatisfiableException
     */
    public static function parse(string $header, int $size): ?self
    {
        $header = trim($header);
        if ($header === '' || !str_starts_with($header, 'bytes=')) {
            return null;
        }
        $spec = trim(substr($header, 6));
        if (str_contains($spec, ',')) {
            return null;
        }
        if (preg_match('/^(\d*)-(\d*)$/', $spec, $match) !== 1 || ($match[1] === '' && $match[2] === '')) {
            return null;
        }

        if ($match[1] === '') {
            $suffix = (int) $match[2];
            if ($suffix === 0 || $size === 0) {
                throw new RangeNotSatisfiableException('Range not satisfiable.');
            }

            return new self(max(0, $size - $suffix), $size - 1);
        }

        $start = (int) $match[1];
        $end = $match[2] === '' ? $size - 1 : min((int) $match[2], $size - 1);
        if ($start >= $size || $start > $end) {
            throw new RangeNotSatisfiableException('Range not satisfiable.');
        }

        return new self($start, $end);
    }
}
