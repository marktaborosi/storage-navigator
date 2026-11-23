<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Entry;

use Marktaborosi\StorageNavigator\Filter\FilterInterface;
use Marktaborosi\StorageNavigator\Path\Path;

/**
 * The contents of one directory. Immutable; sorting and filtering return new instances.
 *
 * @implements \IteratorAggregate<int, Entry>
 */
final readonly class Listing implements \IteratorAggregate, \Countable
{
    /** @var list<Entry> */
    public array $entries;

    /**
     * @param iterable<Entry> $entries
     */
    public function __construct(
        public Path $directory,
        iterable $entries,
    ) {
        $list = [];
        foreach ($entries as $entry) {
            $list[] = $entry;
        }
        $this->entries = $list;
    }

    /**
     * Directories always come first; within each group the given field decides.
     * Names compare naturally and case-insensitively ("file2" before "file10").
     */
    public function sorted(SortField $field = SortField::Name, SortDirection $direction = SortDirection::Ascending): self
    {
        $entries = $this->entries;
        usort($entries, static function (Entry $a, Entry $b) use ($field, $direction): int {
            if ($a->type !== $b->type) {
                return $a->isDirectory() ? -1 : 1;
            }
            $result = match ($field) {
                SortField::Name => 0,
                SortField::Size => ($a->size ?? -1) <=> ($b->size ?? -1),
                SortField::Modified => ($a->lastModified ?? 0) <=> ($b->lastModified ?? 0),
                SortField::Type => strnatcasecmp($a->extension(), $b->extension()),
            };
            if ($result === 0) {
                $result = strnatcasecmp($a->name(), $b->name());
            }

            return $direction === SortDirection::Ascending ? $result : -$result;
        });

        return new self($this->directory, $entries);
    }

    public function filter(FilterInterface $filter): self
    {
        return new self($this->directory, array_filter($this->entries, $filter->matches(...)));
    }

    /**
     * Case-insensitive substring match on the entry name.
     */
    public function search(string $query): self
    {
        $query = trim($query);
        if ($query === '') {
            return $this;
        }

        return new self(
            $this->directory,
            array_filter($this->entries, static fn (Entry $entry): bool => mb_stripos($entry->name(), $query) !== false),
        );
    }

    /**
     * @return list<Entry>
     */
    public function files(): array
    {
        return array_values(array_filter($this->entries, static fn (Entry $entry): bool => $entry->isFile()));
    }

    /**
     * @return list<Entry>
     */
    public function directories(): array
    {
        return array_values(array_filter($this->entries, static fn (Entry $entry): bool => $entry->isDirectory()));
    }

    /**
     * Sum of the known file sizes in bytes.
     */
    public function totalSize(): int
    {
        return array_sum(array_map(static fn (Entry $entry): int => $entry->size ?? 0, $this->files()));
    }

    /**
     * @return \ArrayIterator<int, Entry>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->entries);
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
