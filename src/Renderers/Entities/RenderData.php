<?php

namespace Marktaborosi\StorageNavigator\Renderers\Entities;

use Marktaborosi\StorageNavigator\Entities\FileStructure;

/**
 * Class RenderData
 *
 * Represents the data required for rendering a file browser interface.
 * This includes the current path, root path, file structure, and configuration settings.
 *
 * @package Marktaborosi\StorageBrowser\Renderers\Entities
 */
class RenderData
{
    /**
     * @var string The current directory path being viewed.
     */
    private string $currentPath;

    /**
     * @var FileStructure The structure of files and directories in the current path.
     */
    private FileStructure $structure;

    /**
     * @var string The root directory path of the file browser.
     */
    private string $rootPath;

    /**
     * RenderData constructor.
     *
     * @param string $currentPath The current directory path being viewed.
     * @param string $rootPath The root directory path of the file browser.
     * @param FileStructure $structure The structure of files and directories in the current path.
     */
    public function __construct(
        string $currentPath,
        string $rootPath,
        FileStructure $structure
    ) {
        $this->currentPath = $currentPath;
        $this->rootPath = $rootPath;
        $this->structure = $structure;
    }

    /**
     * Retrieves the root path of the file browser.
     *
     * @return string The root directory path.
     */
    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * Retrieves the current path being viewed.
     *
     * @return string The current directory path.
     */
    public function getCurrentPath(): string
    {
        return $this->currentPath;
    }

    /**
     * Retrieves the file structure for the current path.
     *
     * @return FileStructure The structure of files and directories.
     */
    public function getStructure(): FileStructure
    {
        return $this->structure;
    }
}
