<?php

namespace Marktaborosi\StorageBrowser;

use Exception;
use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserAdapterInterface;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserRendererInterface;
use Marktaborosi\StorageBrowser\Renderers\Factories\RenderDataFactory;

/**
 * Class FileBrowser
 *
 * This class is responsible for rendering a file browser interface by using a specified adapter
 * to retrieve the structure of a directory and then rendering it using a specified renderer.
 * The renderer can vary based on the implementation (e.g., HTML, console).
 *
 * @package Marktaborosi\StorageBrowser
 * @pattern Facade
 */
class StorageBrowser
{
    private StorageBrowserAdapterInterface $adapter;
    private FileBrowserConfig $config;
    private StorageBrowserRendererInterface $renderer;
    private string $rootPath;

    /**
     * Constructor for the FileBrowser class.
     *
     * Initializes the FileBrowser with a specific adapter, renderer, configuration, and root path.
     * Throws an exception if the provided root path does not exist.
     *
     * @param StorageBrowserAdapterInterface  $adapter  The adapter responsible for interacting with the file system.
     * @param StorageBrowserRendererInterface $renderer The renderer responsible for generating the file browser output.
     * @param FileBrowserConfig            $config   Configuration settings for the file browser.
     * @param string                       $rootPath The root directory path to be displayed in the file browser.
     *
     * @throws Exception If the root path does not exist.
     */
    public function __construct(
        StorageBrowserAdapterInterface  $adapter,
        StorageBrowserRendererInterface $renderer,
        FileBrowserConfig               $config,
        string                          $rootPath
    ) {
        if (!$adapter->fileOrDirectoryExists($rootPath)) {
            throw new Exception("Location: [$rootPath] does not exist");
        }

        $this->adapter = $adapter;
        $this->config = $config;
        $this->renderer = $renderer;
        $this->rootPath = $rootPath;
    }

    /**
     * Renders the file browser view.
     *
     * Handles the request for rendering the file browser by delegating the task to the handleRequest method.
     *
     * @throws Exception If an error occurs during rendering.
     */
    public function display(): void
    {
        $this->handleRequest();
    }

    /**
     * Handles incoming requests to either navigate directories, download files, or load the directory structure.
     *
     * Processes the request by checking the navigation handler's state. Depending on the request type,
     * it either changes the directory, downloads a file, or renders the current directory structure.
     *
     * @throws Exception If the file requested for download does not exist or if another error occurs during processing.
     */
    private function handleRequest(): void
    {
        $navigationHandler = $this->renderer->navigationHandler();

        // Change Path
        if ($navigationHandler->isChangePathRequest()) {
            $this->renderer->render(
                RenderDataFactory::make(
                    adapter: $this->adapter,
                    config: $this->config,
                    rootPath: $this->rootPath,
                    currentPath: $navigationHandler->changeToPath()
                ),
            );
        }

        // Download File
        if ($navigationHandler->isDownloadFileRequest()) {
            $downloadFilePath = $navigationHandler->downloadFilePath();
            if ($this->adapter->fileOrDirectoryExists($downloadFilePath)) {
                $this->adapter->downloadFile($downloadFilePath);
            } else {
                throw new Exception("File [$downloadFilePath] does not exist.");
            }
        }

        // Load structure
        if (!$navigationHandler->isChangePathRequest() && !$navigationHandler->isDownloadFileRequest()) {
            $this->renderer->render(
                RenderDataFactory::make(
                    adapter: $this->adapter,
                    config: $this->config,
                    rootPath: $this->rootPath,
                    currentPath: $this->rootPath
                )
            );
        }
    }
}
