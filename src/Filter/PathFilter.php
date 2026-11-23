<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;

/**
 * Matches entries whose path relative to the storage root matches one of the
 * shell-style patterns. "*" also matches slashes, so "private/*" covers everything
 * below "private". Case-insensitive by default.
 */
final readonly class PathFilter implements FilterInterface
{
    /** @var list<string> */
    private array $patterns;

    /**
     * @param list<string> $patterns
     */
    public function __construct(array $patterns, private bool $caseSensitive = false)
    {
        $this->patterns = array_map(static fn (string $p): string => trim(str_replace('\\', '/', $p), '/'), $patterns);
    }

    public function matches(Entry $entry): bool
    {
        $flags = $this->caseSensitive ? 0 : FNM_CASEFOLD;
        $path = $entry->path->toString();
        foreach ($this->patterns as $pattern) {
            if (fnmatch($pattern, $path, $flags)) {
                return true;
            }
        }

        return false;
    }
}
