<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Unit\Filter;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Filter\Filter;
use Marktaborosi\StorageNavigator\Path\Path;
use PHPUnit\Framework\TestCase;

final class FilterTest extends TestCase
{
    private static function file(string $path): Entry
    {
        return Entry::file(Path::fromString($path), 1);
    }

    private static function dir(string $path): Entry
    {
        return Entry::directory(Path::fromString($path));
    }

    public function testNamePatterns(): void
    {
        $filter = Filter::name('*.log', 'README*');

        self::assertTrue($filter->matches(self::file('logs/app.LOG')));
        self::assertTrue($filter->matches(self::file('README.md')));
        self::assertFalse($filter->matches(self::file('app.txt')));
    }

    public function testPathPatternsCoverEverythingBelow(): void
    {
        $filter = Filter::path('private/*');

        self::assertTrue($filter->matches(self::file('private/a/b.txt')));
        self::assertFalse($filter->matches(self::file('public/private.txt')));
    }

    public function testExtensionFilterKeepsDirectoriesVisible(): void
    {
        // Regression: in 2.x an extension filter hid every folder, making navigation impossible.
        $filter = Filter::extension('pdf', '.DOCX');

        self::assertTrue($filter->matches(self::dir('reports')));
        self::assertTrue($filter->matches(self::file('a.PDF')));
        self::assertTrue($filter->matches(self::file('b.docx')));
        self::assertFalse($filter->matches(self::file('c.txt')));
    }

    public function testCombinators(): void
    {
        $filter = Filter::all(Filter::files(), Filter::not(Filter::hidden()), Filter::any(Filter::name('*.txt'), Filter::name('*.md')));

        self::assertTrue($filter->matches(self::file('a.txt')));
        self::assertFalse($filter->matches(self::file('.hidden.txt')));
        self::assertFalse($filter->matches(self::dir('folder.txt')));
        self::assertFalse($filter->matches(self::file('a.pdf')));
        self::assertTrue(Filter::all()->matches(self::file('x')));
        self::assertFalse(Filter::any()->matches(self::file('x')));
    }

    public function testCallback(): void
    {
        $filter = Filter::callback(static fn (Entry $entry): bool => ($entry->size ?? 0) > 0);

        self::assertTrue($filter->matches(self::file('a')));
        self::assertFalse($filter->matches(self::dir('b')));
    }
}
