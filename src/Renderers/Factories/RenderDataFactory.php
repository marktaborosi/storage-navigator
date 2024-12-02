<?php

namespace Marktaborosi\StorageBrowser\Renderers\Factories;

use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserAdapterInterface;
use Marktaborosi\StorageBrowser\Renderers\Entities\RenderData;
use Marktaborosi\StorageBrowser\Structure\FileStructureFilterer;

/**
 * Class RenderDataFactory
 *
 * Factory class responsible for creating instances of RenderData.
 * This class encapsulates the process of constructing RenderData objects,
 * which include file structure, configuration, and paths.
 *
 * @package Marktaborosi\StorageBrowser\Renderers\Factories
 * @pattern Factory
 */
class RenderDataFactory
{
    /**
     * Creates an instance of RenderData using the provided adapter, configuration, root path, and current path.
     *
     * This static method handles the creation of a RenderData instance by using a file browser adapter to
     * retrieve the file structure and applying any necessary filters based on the provided configuration.
     *
     * @param StorageBrowserAdapterInterface $adapter The file browser adapter used to retrieve the file structure.
     * @param FileBrowserConfig              $config The configuration settings for the file browser.
     * @param string                         $rootPath The root directory path of the file browser.
     * @param string                         $currentPath The current directory path within the file browser.
     * @return RenderData Returns an instance of RenderData containing the file structure, paths, and configuration.
     */
    public static function make(
        StorageBrowserAdapterInterface $adapter,
        FileBrowserConfig              $config,
        string                         $rootPath,
        string                         $currentPath
    ): RenderData {
        return new RenderData(
            $currentPath,
            $rootPath,
            FileStructureFilterer::filter($adapter->getFileStructure($currentPath), $config),
            $config
        );
    }
}
