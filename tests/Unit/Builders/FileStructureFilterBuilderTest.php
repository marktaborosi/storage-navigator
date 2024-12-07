<?php

namespace Marktaborosi\StorageNavigator\Tests\Unit\Builders;

use Marktaborosi\StorageNavigator\Builders\FileStructureFilterBuilder;
use Marktaborosi\StorageNavigator\Entities\FileAttribute;
use Marktaborosi\StorageNavigator\Entities\DirectoryAttribute;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class FileStructureFilterBuilderTest
 *
 * Tests the functionality of the FileStructureFilterBuilder class.
 */
class FileStructureFilterBuilderTest extends TestCase
{
    /**
     * Test isFile filter includes only FileAttribute entries.
     */
    public function test_is_file()
    {
        $builder = new FileStructureFilterBuilder();
        $filter = $builder->isFile()->getFilters();

        $file = Mockery::mock(FileAttribute::class);
        $directory = Mockery::mock(DirectoryAttribute::class);

        $this->assertTrue($filter[0]($file));
        $this->assertFalse($filter[0]($directory));
    }

    /**
     * Test isDirectory filter includes only DirectoryAttribute entries.
     */
    public function test_is_directory()
    {
        $builder = new FileStructureFilterBuilder();
        $filter = $builder->isDirectory()->getFilters();

        $file = Mockery::mock(FileAttribute::class);
        $directory = Mockery::mock(DirectoryAttribute::class);

        $this->assertFalse($filter[0]($file));
        $this->assertTrue($filter[0]($directory));
    }

    /**
     * Test nameEquals filter matches the exact name for both files and directories.
     */
    public function test_name_equals()
    {
        $builder = new FileStructureFilterBuilder();
        $filter = $builder->nameEquals(['test.txt', 'documents'])->getFilters();

        $file = Mockery::mock(FileAttribute::class);
        $file->shouldReceive('getFilename')->andReturn('test.txt');

        $directory = Mockery::mock(DirectoryAttribute::class);
        $directory->shouldReceive('getName')->andReturn('documents');

        $nonMatchingFile = Mockery::mock(FileAttribute::class);
        $nonMatchingFile->shouldReceive('getFilename')->andReturn('other.txt');

        $this->assertTrue($filter[0]($file));
        $this->assertTrue($filter[0]($directory));
        $this->assertFalse($filter[0]($nonMatchingFile));
    }

    /**
     * Test nameContains filter matches substrings in names for both files and directories.
     */
    public function test_name_contains()
    {
        $builder = new FileStructureFilterBuilder();
        $filter = $builder->nameContains(['doc', 'test'])->getFilters();

        $file = Mockery::mock(FileAttribute::class);
        $file->shouldReceive('getFilename')->andReturn('testfile.txt');

        $directory = Mockery::mock(DirectoryAttribute::class);
        $directory->shouldReceive('getName')->andReturn('documents');

        $nonMatchingFile = Mockery::mock(FileAttribute::class);
        $nonMatchingFile->shouldReceive('getFilename')->andReturn('example.txt');

        $this->assertTrue($filter[0]($file));
        $this->assertTrue($filter[0]($directory));
        $this->assertFalse($filter[0]($nonMatchingFile));
    }

    /**
     * Test extensionEquals filter matches the exact file extension.
     */
    public function test_extension_equals()
    {
        $builder = new FileStructureFilterBuilder();
        $filter = $builder->extensionEquals(['txt', 'pdf'])->getFilters();

        $file = Mockery::mock(FileAttribute::class);
        $file->shouldReceive('getExtension')->andReturn('txt');

        $nonMatchingFile = Mockery::mock(FileAttribute::class);
        $nonMatchingFile->shouldReceive('getExtension')->andReturn('doc');

        $directory = Mockery::mock(DirectoryAttribute::class); // Should be ignored by the extension filter

        $this->assertTrue($filter[0]($file));
        $this->assertFalse($filter[0]($nonMatchingFile));
        $this->assertFalse($filter[0]($directory));
    }

    /**
     * Test nameNotEquals filter excludes specified names for both files and directories.
     */
    public function test_name_not_equals()
    {
        $builder = new FileStructureFilterBuilder();
        $filter = $builder->nameNotEquals(['test.txt', 'documents'])->getFilters();

        $file = Mockery::mock(FileAttribute::class);
        $file->shouldReceive('getFilename')->andReturn('example.txt');

        $directory = Mockery::mock(DirectoryAttribute::class);
        $directory->shouldReceive('getName')->andReturn('exampledir');

        $nonMatchingFile = Mockery::mock(FileAttribute::class);
        $nonMatchingFile->shouldReceive('getFilename')->andReturn('test.txt');

        $this->assertTrue($filter[0]($file));
        $this->assertTrue($filter[0]($directory));
        $this->assertFalse($filter[0]($nonMatchingFile));
    }

    /**
     * Tear down Mockery after tests.
     */
    protected function tearDown(): void
    {
        Mockery::close();
    }
}