<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Path;

use Marktaborosi\StorageNavigator\Exception\InvalidPathException;

/**
 * A normalized path relative to the storage root.
 *
 * This is the only way a client-supplied path enters the library, and its rules make
 * escaping the root impossible by construction:
 *
 *  - backslashes are treated as separators, empty and "." segments are dropped;
 *  - ".." segments are rejected outright (never resolved);
 *  - NUL bytes, control characters and invalid UTF-8 are rejected;
 *  - each segment is at most 255 bytes, the whole path at most 4096 bytes.
 *
 * The root itself is the empty path. String form never has leading or trailing slashes.
 */
final class Path implements \Stringable
{
    public const int MAX_LENGTH = 4096;
    public const int MAX_SEGMENT_LENGTH = 255;

    /** @var list<string> */
    private readonly array $segments;

    /**
     * @param list<string> $segments already validated segments
     */
    private function __construct(array $segments)
    {
        $this->segments = $segments;
    }

    public static function root(): self
    {
        return new self([]);
    }

    /**
     * Parses an untrusted path (for example from a query string).
     *
     * @throws InvalidPathException
     */
    public static function fromString(string $path): self
    {
        if (strlen($path) > self::MAX_LENGTH) {
            throw new InvalidPathException('The path is too long.');
        }

        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            self::assertValidSegment($segment);
            $segments[] = $segment;
        }

        return new self($segments);
    }

    /**
     * Validates a single file or directory name supplied by a client (upload, rename, new folder).
     *
     * Stricter than a path segment: no separators at all, no leading or trailing whitespace,
     * and no trailing dot (Windows would silently strip it).
     *
     * @throws InvalidPathException
     */
    public static function assertValidName(string $name): void
    {
        if ($name === '' || trim($name) !== $name) {
            throw new InvalidPathException('The name must not be empty or start or end with whitespace.');
        }
        if (str_contains($name, '/') || str_contains($name, '\\')) {
            throw new InvalidPathException('The name must not contain slashes.');
        }
        if ($name === '.' || str_ends_with($name, '.')) {
            throw new InvalidPathException('The name must not end with a dot.');
        }
        self::assertValidSegment($name);
    }

    public function isRoot(): bool
    {
        return $this->segments === [];
    }

    /**
     * @return list<string>
     */
    public function segments(): array
    {
        return $this->segments;
    }

    /**
     * Last segment, or an empty string for the root.
     */
    public function name(): string
    {
        return $this->segments === [] ? '' : $this->segments[array_key_last($this->segments)];
    }

    /**
     * Lower-case extension of the last segment without the dot ("" when there is none).
     * A leading dot alone (".env") is not an extension.
     */
    public function extension(): string
    {
        $name = $this->name();
        $dot = strrpos($name, '.');

        return $dot === false || $dot === 0 ? '' : mb_strtolower(substr($name, $dot + 1));
    }

    /**
     * Parent directory. The parent of the root is the root.
     */
    public function parent(): self
    {
        return new self(array_slice($this->segments, 0, -1));
    }

    /**
     * Appends one validated name.
     *
     * @throws InvalidPathException
     */
    public function child(string $name): self
    {
        self::assertValidSegment($name);
        if ($name === '.' || str_contains($name, '/') || str_contains($name, '\\')) {
            throw new InvalidPathException('Invalid name.');
        }

        return new self([...$this->segments, $name]);
    }

    /**
     * The root and every directory leading to this path, root first, this path excluded.
     *
     * @return list<self>
     */
    public function ancestors(): array
    {
        $ancestors = [];
        for ($i = 0, $count = count($this->segments); $i < $count; $i++) {
            $ancestors[] = new self(array_slice($this->segments, 0, $i));
        }

        return $ancestors;
    }

    public function isDescendantOf(self $other): bool
    {
        $length = count($other->segments);

        return count($this->segments) > $length && array_slice($this->segments, 0, $length) === $other->segments;
    }

    public function equals(self $other): bool
    {
        return $this->segments === $other->segments;
    }

    /**
     * Path relative to $base. $base must be an ancestor of (or equal to) this path.
     */
    public function relativeTo(self $base): string
    {
        if (!$this->equals($base) && !$this->isDescendantOf($base)) {
            throw new \LogicException(sprintf('"%s" is not inside "%s".', $this, $base));
        }

        return implode('/', array_slice($this->segments, count($base->segments)));
    }

    public function toString(): string
    {
        return implode('/', $this->segments);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private static function assertValidSegment(string $segment): void
    {
        if ($segment === '..') {
            throw new InvalidPathException('Parent directory references ("..") are not allowed.');
        }
        if (strlen($segment) > self::MAX_SEGMENT_LENGTH) {
            throw new InvalidPathException('A path segment is too long.');
        }
        if (!mb_check_encoding($segment, 'UTF-8')) {
            throw new InvalidPathException('The path is not valid UTF-8.');
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $segment) === 1) {
            throw new InvalidPathException('The path contains control characters.');
        }
    }
}
