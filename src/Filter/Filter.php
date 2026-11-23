<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Entry\EntryType;

/**
 * Factory for the built-in filters.
 *
 * <code>
 * Filter::all(
 *     Filter::not(Filter::name('*.log', 'tmp')),
 *     Filter::not(Filter::path('private/*')),
 * );
 * </code>
 */
final class Filter
{
    private function __construct()
    {
    }

    public static function all(FilterInterface ...$filters): FilterInterface
    {
        return new AllOf(...$filters);
    }

    public static function any(FilterInterface ...$filters): FilterInterface
    {
        return new AnyOf(...$filters);
    }

    public static function not(FilterInterface $filter): FilterInterface
    {
        return new Not($filter);
    }

    /**
     * Name matches one of the shell-style patterns ("*.log", "backup-??", "README").
     */
    public static function name(string ...$patterns): FilterInterface
    {
        return new NameFilter(array_values($patterns));
    }

    /**
     * Path relative to the root matches one of the shell-style patterns ("private/*").
     */
    public static function path(string ...$patterns): FilterInterface
    {
        return new PathFilter(array_values($patterns));
    }

    /**
     * Files with one of the extensions; directories always pass.
     */
    public static function extension(string ...$extensions): FilterInterface
    {
        return new ExtensionFilter(array_values($extensions));
    }

    public static function files(): FilterInterface
    {
        return new TypeFilter(EntryType::File);
    }

    public static function directories(): FilterInterface
    {
        return new TypeFilter(EntryType::Directory);
    }

    public static function hidden(): FilterInterface
    {
        return new HiddenFilter();
    }

    /**
     * @param callable(Entry): bool $callback
     */
    public static function callback(callable $callback): FilterInterface
    {
        return new CallbackFilter($callback);
    }
}
