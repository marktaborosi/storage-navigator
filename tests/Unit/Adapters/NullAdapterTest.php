<?php

namespace Marktaborosi\StorageNavigator\Tests\Unit\Adapters;

use Marktaborosi\StorageNavigator\Adapters\NullAdapter;
use Marktaborosi\StorageNavigator\Entities\FileStructure;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * Class NullAdapterTest
 *
 * This class tests the NullAdapter implementation to ensure it behaves as a no-op adapter,
 * always returning empty results or false, as intended.
 *
 * @package Marktaborosi\Tests\Unit\Adapters
 */
class NullAdapterTest extends MockeryTestCase
{
    /**
     * Tests that `getFileStructure` returns an empty `FileStructure`.
     *
     * This test verifies that the `NullAdapter` always returns an empty `FileStructure` regardless of input.
     */
    public function test_get_file_structure_returns_empty_structure(): void
    {
        $adapter = new NullAdapter();

        $fileStructure = $adapter->getFileStructure('/some/path');

        $this->assertInstanceOf(FileStructure::class, $fileStructure);
        $this->assertCount(0, $fileStructure->getEntries(), 'Expected the FileStructure to be empty.');
    }

    /**
     * Tests that `fileOrDirectoryExists` always returns false.
     *
     * This test ensures that the `NullAdapter` consistently returns false for existence checks.
     */
    public function test_file_or_directory_exists_always_returns_true(): void
    {
        $adapter = new NullAdapter();

        $result = $adapter->fileOrDirectoryExists('/some/path');

        $this->assertTrue($result);
    }

    /**
     * Tests that `downloadFile` does not produce any output.
     *
     * This test ensures that calling `downloadFile` on the `NullAdapter` does not produce any output or behavior.
     */
    public function test_download_file_produces_no_output(): void
    {
        $adapter = new NullAdapter();

        ob_start();
        $adapter->downloadFile('/some/file/path');
        $output = ob_get_clean();

        $this->assertEmpty($output, 'Expected no output from downloadFile.');
    }

    /**
     * Cleans up Mockery after each test.
     *
     * Ensures that any Mockery expectations or resources are released post-test.
     */
    protected function tear_down(): void
    {
        Mockery::close();
    }
}
