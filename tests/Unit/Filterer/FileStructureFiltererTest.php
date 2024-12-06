<?php

namespace Marktaborosi\StorageNavigator\Tests\Unit\Filterer;

use Marktaborosi\StorageNavigator\Builders\FileStructureFilterBuilder;
use Marktaborosi\StorageNavigator\Entities\FileAttribute;
use Marktaborosi\StorageNavigator\Entities\DirectoryAttribute;
use Marktaborosi\StorageNavigator\Entities\FileStructure;
use Marktaborosi\StorageNavigator\Filterer\FileStructureFilterer;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class FileStructureFiltererTest
 *
 * Unit tests for the FileStructureFilterer class, verifying its ability to filter file structure
 * entries based on various filters and conditions. This includes filtering by type (file or directory),
 * multiple filter combinations, and testing with no matching or empty filter scenarios.
 */
class FileStructureFiltererTest extends TestCase
{
    /**
     * Test filtering file entries using FileStructureFilterBuilder.
     *
     * This test validates that the FileStructureFilterer correctly filters files based on
     * filters provided by the FileStructureFilterBuilder mock object.
     */
    public function test_filter_with_filter_builder()
    {
        // Mock FileAttribute with expected methods
        $file = Mockery::mock(FileAttribute::class);
        $file->shouldReceive('getFilename')->andReturn('file.txt');
        $file->shouldReceive('getDirectoryPath')->andReturn('/mock/path');
        $file->shouldReceive('getByteSize')->andReturn(123);
        $file->shouldReceive('getLastModified')->andReturn(111555);
        $file->shouldReceive('getExtension')->andReturn('txt');
        $file->shouldReceive('isFile')->andReturn(true);

        // Mock DirectoryAttribute (optional for this test)
        $directory = Mockery::mock(DirectoryAttribute::class);
        $directory->shouldReceive('getName')->andReturn('mock-directory');

        $entries = [$file, $directory];

        // Mock FileStructureFilterBuilder with filter functions
        $builder = Mockery::mock(FileStructureFilterBuilder::class);
        $builder->shouldReceive('getFilters')->once()->andReturn([
            function ($entry) {
                return $entry instanceof FileAttribute;
            },
        ]);

        $filteredStructure = FileStructureFilterer::filter($entries, $builder);

        // Assertions
        $this->assertInstanceOf(FileStructure::class, $filteredStructure);
        $this->assertCount(1, $filteredStructure->toArray());
        $this->assertInstanceOf(FileAttribute::class, $filteredStructure->getFiles()[0]);
        $this->assertSame('file.txt', $filteredStructure->toArray()[0]['filename']);
        $this->assertSame('txt', $filteredStructure->toArray()[0]['extension']);
        $this->assertSame('/mock/path', $filteredStructure->toArray()[0]['directory_path']);
        $this->assertSame(123.0, $filteredStructure->toArray()[0]['size']['value']);
        $this->assertSame('111555', $filteredStructure->toArray()[0]['last_modified']);
    }

    /**
     * Test filtering files with an array of callable filters for "isFile".
     *
     * Ensures that only file entries are included when the appropriate filter is applied.
     */
    public function test_filter_with_callable_filters_for_is_file()
    {
        $file = Mockery::mock(FileAttribute::class);
        $directory = Mockery::mock(DirectoryAttribute::class);

        $file->shouldReceive('getFilename')->andReturn('file.txt');
        $file->shouldReceive('getDirectoryPath')->andReturn('/mock/path');
        $file->shouldReceive('getByteSize')->andReturn(123);
        $file->shouldReceive('getLastModified')->andReturn(111555);
        $file->shouldReceive('getExtension')->andReturn('txt');
        $file->shouldReceive('isFile')->andReturn(true);

        $directory->shouldReceive('isDir')->andReturn(true);
        $directory->shouldReceive('getName')->andReturn('dir');
        $directory->shouldReceive('getPath')->andReturn('/mock/path');

        $entries = [$file, $directory];

        $filters = [
            function ($entry) {
                return $entry instanceof FileAttribute;
            },
        ];

        $filteredStructure = FileStructureFilterer::filter($entries, $filters);

        // Assertions
        $this->assertInstanceOf(FileStructure::class, $filteredStructure);
        $this->assertCount(1, $filteredStructure->toArray());
        $this->assertInstanceOf(FileAttribute::class, $filteredStructure->getEntries()[0]);
        $this->assertSame('file.txt', $filteredStructure->toArray()[0]['filename']);
        $this->assertSame('txt', $filteredStructure->toArray()[0]['extension']);
        $this->assertSame('/mock/path', $filteredStructure->toArray()[0]['directory_path']);
        $this->assertSame(123.0, $filteredStructure->toArray()[0]['size']['value']);
        $this->assertSame('111555', $filteredStructure->toArray()[0]['last_modified']);
    }

    /**
     * Test filtering directories using a callable filter for "isDirectory".
     *
     * Validates that only directory entries are included when filtering by directory type.
     */
    public function test_filter_with_callable_filters_for_is_directory()
    {
        $file = Mockery::mock(FileAttribute::class);
        $directory = Mockery::mock(DirectoryAttribute::class);

        $directory->shouldReceive('isDir')->andReturn(true);
        $directory->shouldReceive('getName')->andReturn('dir');
        $directory->shouldReceive('getPath')->andReturn('/mock/path');
        $directory->shouldReceive('getLastModified')->andReturn(987);

        $entries = [$file, $directory];

        $filters = [
            function ($entry) {
                return $entry instanceof DirectoryAttribute;
            },
        ];

        $filteredStructure = FileStructureFilterer::filter($entries, $filters);

        // Assertions
        $this->assertInstanceOf(FileStructure::class, $filteredStructure);
        $this->assertCount(1, $filteredStructure->toArray());
        $this->assertInstanceOf(DirectoryAttribute::class, $filteredStructure->getEntries()[1]);
        $this->assertSame('dir', $filteredStructure->toArray()[0]['type']);
        $this->assertSame('dir', $filteredStructure->toArray()[0]['name']);
        $this->assertSame('/mock/path', $filteredStructure->toArray()[0]['path']);
        $this->assertSame(987, $filteredStructure->toArray()[0]['last_modified']);
    }

    /**
     * Test filtering with no matching entries.
     *
     * Ensures that the resulting structure is empty when no entries match the provided filters.
     */
    public function test_filter_with_no_matching_entries()
    {
        $entries = [];
        $filters = [
            function ($entry) {
                return false; // No entries match this filter
            },
        ];

        $filteredStructure = FileStructureFilterer::filter($entries, $filters);

        // Assertions
        $this->assertInstanceOf(FileStructure::class, $filteredStructure);
        $this->assertCount(0, $filteredStructure->toArray());
    }

    /**
     * Test filtering with multiple filters.
     *
     * Verifies that the filtering works correctly when multiple filter conditions are applied.
     */
    public function test_filter_with_multiple_filters()
    {
        $file = Mockery::mock(FileAttribute::class);
        $directory = Mockery::mock(DirectoryAttribute::class);

        $file->shouldReceive('getFilename')->andReturn('file.txt');
        $file->shouldReceive('getDirectoryPath')->andReturn('/mock/path');
        $file->shouldReceive('getByteSize')->andReturn(123);
        $file->shouldReceive('getLastModified')->andReturn(111555);
        $file->shouldReceive('getExtension')->andReturn('txt');
        $file->shouldReceive('isFile')->andReturn(true);

        $entries = [$file, $directory];

        $filters = [
            function ($entry) {
                return $entry instanceof FileAttribute;
            },
            function ($entry) {
                return method_exists($entry, 'getFilename');
            },
        ];

        $filteredStructure = FileStructureFilterer::filter($entries, $filters);

        // Assertions
        $this->assertInstanceOf(FileStructure::class, $filteredStructure);
        $this->assertCount(1, $filteredStructure->toArray());
        $this->assertInstanceOf(FileAttribute::class, $filteredStructure->getEntries()[0]);
    }

    /**
     * Test filtering with an empty filter list.
     *
     * Ensures that all entries are returned when no filters are applied.
     */
    public function test_filter_with_empty_filter_list()
    {
        $file = Mockery::mock(FileAttribute::class);
        $directory = Mockery::mock(DirectoryAttribute::class);

        $file->shouldReceive('getFilename')->andReturn('file.txt');
        $file->shouldReceive('getDirectoryPath')->andReturn('/mock/path');
        $file->shouldReceive('getByteSize')->andReturn(123);
        $file->shouldReceive('getLastModified')->andReturn(111555);
        $file->shouldReceive('getExtension')->andReturn('txt');
        $file->shouldReceive('isFile')->andReturn(true);

        $directory->shouldReceive('getName')->andReturn('dir');
        $directory->shouldReceive('getPath')->andReturn('/mockery/path');
        $directory->shouldReceive('getLastModified')->andReturn(111333);

        $entries = [$file, $directory];

        $filters = []; // No filters applied

        $filteredStructure = FileStructureFilterer::filter($entries, $filters);

        // Assertions
        $this->assertInstanceOf(FileStructure::class, $filteredStructure);
        $this->assertCount(2, $filteredStructure->toArray());
    }

    /**
     * Tear down Mockery after tests.
     *
     * Cleans up the Mockery framework after each test case.
     */
    protected function tearDown(): void
    {
        Mockery::close();
    }
}
