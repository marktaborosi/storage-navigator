<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Unit\Entry;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Entry\Listing;
use Marktaborosi\StorageNavigator\Entry\SortDirection;
use Marktaborosi\StorageNavigator\Entry\SortField;
use Marktaborosi\StorageNavigator\Filter\Filter;
use Marktaborosi\StorageNavigator\Path\Path;
use PHPUnit\Framework\TestCase;

final class ListingTest extends TestCase
{
    private Listing $listing;

    protected function setUp(): void
    {
        $this->listing = new Listing(Path::root(), [
            Entry::file(Path::fromString('file10.txt'), 300, 100),
            Entry::file(Path::fromString('File2.txt'), 100, 300),
            Entry::directory(Path::fromString('zeta'), 50),
            Entry::file(Path::fromString('archive.zip'), null, 200),
            Entry::directory(Path::fromString('Alpha'), 10),
        ]);
    }

    /**
     * @return list<string>
     */
    private static function names(Listing $listing): array
    {
        return array_map(static fn (Entry $e): string => $e->name(), $listing->entries);
    }

    public function testSortsDirectoriesFirstAndNamesNaturally(): void
    {
        self::assertSame(['Alpha', 'zeta', 'archive.zip', 'File2.txt', 'file10.txt'], self::names($this->listing->sorted()));
        self::assertSame(['zeta', 'Alpha', 'file10.txt', 'File2.txt', 'archive.zip'], self::names($this->listing->sorted(SortField::Name, SortDirection::Descending)));
    }

    public function testSortsBySizeAndDate(): void
    {
        self::assertSame(['Alpha', 'zeta', 'archive.zip', 'File2.txt', 'file10.txt'], self::names($this->listing->sorted(SortField::Size)));
        self::assertSame(['zeta', 'Alpha', 'File2.txt', 'archive.zip', 'file10.txt'], self::names($this->listing->sorted(SortField::Modified, SortDirection::Descending)));
    }

    public function testSearchIsCaseInsensitive(): void
    {
        self::assertSame(['file10.txt', 'File2.txt'], self::names($this->listing->search('FILE')));
        self::assertCount(5, $this->listing->search('  '));
    }

    public function testFilterAndTotals(): void
    {
        $files = $this->listing->filter(Filter::files());

        self::assertCount(3, $files);
        self::assertCount(2, $this->listing->directories());
        self::assertSame(400, $this->listing->totalSize());
    }
}
