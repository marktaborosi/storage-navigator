<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;

/**
 * Matches entries whose name matches one of the shell-style patterns
 * (`*`, `?`, `[abc]`), for example "*.log" or "README*". Case-insensitive by default.
 */
final readonly class NameFilter implements FilterInterface
{
    /** @var list<string> */
    private array $patterns;

    /**
     * @param list<string> $patterns
     */
    public function __construct(array $patterns, private bool $caseSensitive = false)
    {
        $this->patterns = $patterns;
    }

    public function matches(Entry $entry): bool
    {
        $flags = $this->caseSensitive ? 0 : FNM_CASEFOLD;
        foreach ($this->patterns as $pattern) {
            if (fnmatch($pattern, $entry->name(), $flags)) {
                return true;
            }
        }

        return false;
    }
}
