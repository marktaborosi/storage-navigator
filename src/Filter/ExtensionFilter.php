<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;

/**
 * Matches files with one of the given extensions (case-insensitive, without the dot).
 *
 * Directories always match, so restricting a listing to "pdf" still lets users open
 * folders. Use TypeFilter to hide directories explicitly.
 */
final readonly class ExtensionFilter implements FilterInterface
{
    /** @var list<string> */
    private array $extensions;

    /**
     * @param list<string> $extensions
     */
    public function __construct(array $extensions)
    {
        $this->extensions = array_map(static fn (string $e): string => mb_strtolower(ltrim($e, '.')), $extensions);
    }

    public function matches(Entry $entry): bool
    {
        return $entry->isDirectory() || in_array($entry->extension(), $this->extensions, true);
    }
}
