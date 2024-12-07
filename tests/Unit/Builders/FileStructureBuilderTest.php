<?php

namespace Marktaborosi\StorageNavigator\Tests\Unit\Builders;

use Marktaborosi\StorageNavigator\Builders\FileStructureBuilder;
use Marktaborosi\StorageNavigator\Entities\DirectoryAttribute;
use Marktaborosi\StorageNavigator\Entities\FileAttribute;
use Marktaborosi\StorageNavigator\Entities\FileStructure;
use PHPUnit\Framework\TestCase;

/**
 * Class FileStructureBuilderTest
 *
 * This class tests the functionality of the `FileStructureBuilder` class,
 * ensuring it correctly handles the addition, sorting, and building of
 * file and directory structures.
 *
 * @package Marktaborosi\Tests\Unit\Builders
 */
class FileStructureBuilderTest extends TestCase
{
    /**
     * Tests adding a single file to the builder.
     *
     * Verifies that a single file can be added to the `FileStructureBuilder`
     * and that the resulting `FileStructure` contains exactly one file.
     * Ensures the added file matches the expected attributes.
     */
    public function test_add_file(): void
    {
        $file = new FileAttribute(
            directoryPath: '',
            filename: 'file1.txt',
            extension: 'txt',
            byteSize: 100,
            lastModified: time()
        );

        $builder = new FileStructureBuilder();
        $builder->addFile($file);
        $fileStructure = $builder->build();

        $this->assertInstanceOf(FileStructure::class, $fileStructure);
        $this->assertCount(1, $fileStructure->getFiles());
        $this->assertSame($file, $fileStructure->getFiles()[0]);
    }

    /**
     * Tests adding a single directory to the builder.
     *
     * Verifies that a single directory can be added to the `FileStructureBuilder`
     * and that the resulting `FileStructure` contains exactly one directory.
     * Ensures the added directory matches the expected attributes.
     */
    public function test_add_directory(): void
    {
        $directory = new DirectoryAttribute(
            name: 'dir1',
            path: '',
            lastModified: time()
        );

        $builder = new FileStructureBuilder();
        $builder->addDirectory($directory);
        $fileStructure = $builder->build();

        $this->assertInstanceOf(FileStructure::class, $fileStructure);
        $this->assertCount(1, $fileStructure->getDirectories());
        $this->assertSame($directory, $fileStructure->getDirectories()[0]);
    }

    /**
     * Tests sorting files and directories in the structure.
     *
     * Verifies that directories are sorted alphabetically by name in ascending order,
     * followed by files sorted alphabetically by filename in ascending order.
     * Ensures the sorted order matches the expected sequence.
     */
    public function test_sort_by(): void
    {
        $directory1 = new DirectoryAttribute(
            name: 'dirB',
            path: '',
            lastModified: time()
        );

        $directory2 = new DirectoryAttribute(
            name: 'dirA',
            path: '',
            lastModified: time()
        );

        $file1 = new FileAttribute(
            directoryPath: '',
            filename: 'fileB.txt',
            extension: 'txt',
            byteSize: 100,
            lastModified: time()
        );

        $file2 = new FileAttribute(
            directoryPath: '',
            filename: 'fileA.txt',
            extension: 'txt',
            byteSize: 100,
            lastModified: time()
        );

        $builder = new FileStructureBuilder();
        $builder->addDirectory($directory1);
        $builder->addDirectory($directory2);
        $builder->addFile($file1);
        $builder->addFile($file2);
        $builder->sortByAZ();
        $fileStructure = $builder->build();

        $this->assertInstanceOf(FileStructure::class, $fileStructure);

        $directories = $fileStructure->getDirectories();
        $this->assertCount(2, $directories);
        $this->assertSame($directory2, $directories[0]); // dirA should be first
        $this->assertSame($directory1, $directories[1]); // dirB should be second

        $files = $fileStructure->getFiles();
        $this->assertCount(2, $files);
        $this->assertSame($file2, $files[2]); // fileA.txt should be first
        $this->assertSame($file1, $files[3]); // fileB.txt should be second
    }

    /**
     * Tests building the file structure after adding multiple files and directories.
     *
     * Verifies that the `FileStructureBuilder` correctly builds a `FileStructure`
     * when multiple files and directories are added. Ensures the resulting structure
     * contains the expected number of files and directories and that they match
     * the ones added.
     */
    public function test_build_file_structure(): void
    {
        $directory1 = new DirectoryAttribute(
            name: 'dir1',
            path: '',
            lastModified: time()
        );

        $file1 = new FileAttribute(
            directoryPath: '',
            filename: 'file1.txt',
            extension: 'txt',
            byteSize: 100,
            lastModified: time()
        );

        $builder = new FileStructureBuilder();
        $builder->addDirectory($directory1);
        $builder->addFile($file1);
        $fileStructure = $builder->build();

        $this->assertInstanceOf(FileStructure::class, $fileStructure);

        $this->assertCount(1, $fileStructure->getDirectories());
        $this->assertSame($directory1, $fileStructure->getDirectories()[0]);

        $this->assertCount(1, $fileStructure->getFiles());
        $this->assertSame($file1, $fileStructure->getFiles()[1]);
    }

    /**
     * Tests the sorting and building of an empty structure.
     *
     * Verifies that the `FileStructureBuilder` can handle an empty structure,
     * i.e., when no files or directories are added. Ensures the resulting `FileStructure`
     * is empty and contains no files or directories.
     */
    public function test_build_empty_structure(): void
    {
        $builder = new FileStructureBuilder();
        $fileStructure = $builder->sortByAZ()->build();

        $this->assertInstanceOf(FileStructure::class, $fileStructure);
        $this->assertEmpty($fileStructure->getDirectories());
        $this->assertEmpty($fileStructure->getFiles());
    }
}
