<?php

namespace Marktaborosi\Tests\Unit\Renderers\Factories;

use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserAdapterInterface;
use Marktaborosi\StorageBrowser\Renderers\Entities\RenderData;
use Marktaborosi\StorageBrowser\Renderers\Factories\RenderDataFactory;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class RenderDataFactoryTest
 *
 * This class tests the RenderDataFactory class to ensure that it correctly creates instances of RenderData
 * based on different configurations, paths, and file structures.
 *
 * @package Unit\Renderers\Factories
 */
class RenderDataFactoryTest extends TestCase
{
    /**
     * Tests that the make() method correctly creates a RenderData instance with the expected properties.
     *
     * @throws Exception
     */
    public function test_make_creates_render_data_correctly(): void
    {
        $adapterMock = $this->createMock(StorageBrowserAdapterInterface::class);
        $configMock = $this->createMock(FileBrowserConfig::class);
        $mockFileStructure = $this->createMock(FileStructure::class);

        $rootPath = '/root/path';
        $currentPath = '/current/path';

        $adapterMock->expects($this->once())
            ->method('getFileStructure')
            ->with($currentPath)
            ->willReturn($mockFileStructure);

        $renderData = RenderDataFactory::make($adapterMock, $configMock, $rootPath, $currentPath);

        $this->assertInstanceOf(RenderData::class, $renderData);
        $this->assertEquals($rootPath, $renderData->getRootPath());
        $this->assertEquals($currentPath, $renderData->getCurrentPath());
        $this->assertEquals($mockFileStructure->toArray(), $renderData->getStructure()->toArray());
        $this->assertSame($configMock, $renderData->getConfiguration());
    }

    /**
     * Tests that the make() method handles the case when the current path is equal to the root path.
     *
     * @throws Exception
     */
    public function test_make_handles_current_path_equal_to_root_path(): void
    {
        $adapterMock = $this->createMock(StorageBrowserAdapterInterface::class);
        $configMock = $this->createMock(FileBrowserConfig::class);
        $mockFileStructure = $this->createMock(FileStructure::class);

        $rootPath = '/root/path';
        $currentPath = '/root/path';

        $adapterMock->expects($this->once())
            ->method('getFileStructure')
            ->with($currentPath)
            ->willReturn($mockFileStructure);

        $renderData = RenderDataFactory::make($adapterMock, $configMock, $rootPath, $currentPath);

        $this->assertInstanceOf(RenderData::class, $renderData);
        $this->assertEquals($rootPath, $renderData->getRootPath());
        $this->assertEquals($currentPath, $renderData->getCurrentPath());
        $this->assertEquals($mockFileStructure->toArray(), $renderData->getStructure()->toArray());
        $this->assertSame($configMock, $renderData->getConfiguration());
    }

    /**
     * Tests that the make() method handles an empty current path.
     *
     * @throws Exception
     */
    public function test_make_handles_empty_current_path(): void
    {
        $adapterMock = $this->createMock(StorageBrowserAdapterInterface::class);
        $configMock = $this->createMock(FileBrowserConfig::class);
        $mockFileStructure = $this->createMock(FileStructure::class);

        $rootPath = '/root/path';
        $currentPath = '';

        $adapterMock->expects($this->once())
            ->method('getFileStructure')
            ->with($currentPath)
            ->willReturn($mockFileStructure);

        $renderData = RenderDataFactory::make($adapterMock, $configMock, $rootPath, $currentPath);

        $this->assertInstanceOf(RenderData::class, $renderData);
        $this->assertEquals($rootPath, $renderData->getRootPath());
        $this->assertEquals($currentPath, $renderData->getCurrentPath());
        $this->assertEquals($mockFileStructure->toArray(), $renderData->getStructure()->toArray());
        $this->assertSame($configMock, $renderData->getConfiguration());
    }

    /**
     * Tests the make() method with different configurations.
     *
     * @throws Exception
     */
    public function test_make_with_different_configurations(): void
    {
        $adapterMock = $this->createMock(StorageBrowserAdapterInterface::class);
        $configMock1 = $this->createMock(FileBrowserConfig::class);
        $configMock2 = $this->createMock(FileBrowserConfig::class);
        $mockFileStructure = $this->createMock(FileStructure::class);

        $rootPath = '/root/path';
        $currentPath = '/current/path';

        $adapterMock->expects($this->exactly(2))
            ->method('getFileStructure')
            ->with($currentPath)
            ->willReturn($mockFileStructure);

        $renderData1 = RenderDataFactory::make($adapterMock, $configMock1, $rootPath, $currentPath);
        $renderData2 = RenderDataFactory::make($adapterMock, $configMock2, $rootPath, $currentPath);

        $this->assertNotSame($renderData1->getConfiguration(), $renderData2->getConfiguration());
    }

    /**
     * Tests the make() method with an empty file structure.
     *
     * @throws Exception
     */
    public function test_make_with_empty_file_structure(): void
    {
        $adapterMock = $this->createMock(StorageBrowserAdapterInterface::class);
        $configMock = $this->createMock(FileBrowserConfig::class);
        $emptyFileStructure = $this->createMock(FileStructure::class);

        $emptyFileStructure->method('toArray')->willReturn([]);

        $rootPath = '/root/path';
        $currentPath = '/current/path';

        $adapterMock->expects($this->once())
            ->method('getFileStructure')
            ->with($currentPath)
            ->willReturn($emptyFileStructure);

        $renderData = RenderDataFactory::make($adapterMock, $configMock, $rootPath, $currentPath);

        $this->assertInstanceOf(RenderData::class, $renderData);
        $this->assertEmpty($renderData->getStructure()->toArray());
    }
}
