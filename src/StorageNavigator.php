<?php

namespace Marktaborosi\StorageNavigator;

use Exception;
use Marktaborosi\StorageNavigator\Builders\FileStructureFilterBuilder;
use Marktaborosi\StorageNavigator\Filterer\FileStructureFilterer;
use Marktaborosi\StorageNavigator\Interfaces\StorageNavigatorAdapterInterface;
use Marktaborosi\StorageNavigator\Interfaces\StorageNavigatorRendererInterface;
use Marktaborosi\StorageNavigator\Renderers\Entities\RenderData;

/**
 * Class StorageNavigator
 *
 * A facade class for managing and rendering a file browser interface.
 * This class integrates a file system adapter to interact with storage,
 * a renderer to display the browser output, and optional filters to manage
 * displayed file structures. It supports navigation, directory browsing, and file downloading.
 *
 * @package Marktaborosi\StorageNavigator
 * @pattern Facade
 */
class StorageNavigator
{
    /**
     * @var StorageNavigatorAdapterInterface The adapter used to interact with the file system.
     */
    private StorageNavigatorAdapterInterface $adapter;

    /**
     * @var StorageNavigatorRendererInterface The renderer responsible for generating the file browser output.
     */
    private StorageNavigatorRendererInterface $renderer;

    /**
     * @var string The root directory path for the file browser.
     */
    private string $rootPath;

    /**
     * @var FileStructureFilterBuilder|null Optional builder for configuring file structure filters.
     */
    private ?FileStructureFilterBuilder $filterBuilder;

    /**
     * Constructor for the StorageNavigator.
     *
     * @param StorageNavigatorAdapterInterface  $adapter The adapter used to interact with the file system.
     * @param StorageNavigatorRendererInterface $renderer The renderer responsible for outputting the file browser.
     * @param string                            $rootPath The root directory for the file browser.
     * @param FileStructureFilterBuilder|null   $filterBuilder Optional filter builder for file structure filtering.
     * @throws Exception If the specified root path does not exist.
     */
    public function __construct(
        StorageNavigatorAdapterInterface  $adapter,
        StorageNavigatorRendererInterface $renderer,
        string                            $rootPath,
        ?FileStructureFilterBuilder       $filterBuilder,
    ) {
        if (!$adapter->fileOrDirectoryExists($rootPath)) {
            throw new Exception("Location: [$rootPath] does not exist");
        }

        $this->adapter = $adapter;
        $this->renderer = $renderer;
        $this->rootPath = $rootPath;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * Display the file browser interface.
     *
     * This method processes navigation or download requests, and renders the file browser
     * output using the renderer. It handles directory changes, file downloads, and structure rendering.
     *
     * @return void
     * @throws Exception If an invalid file path is accessed.
     */
    public function display(): void
    {
        $this->handleRequest();
    }

    /**
     * Handle navigation and rendering requests.
     *
     * This method evaluates the current navigation state based on the renderer's navigation handler,
     * and processes directory changes, file downloads, or structure rendering accordingly.
     *
     * - **Change Path Request**: Navigates to a different directory and renders its structure.
     * - **Download File Request**: Initiates the download of a specified file.
     * - **Default**: Renders the root directory structure.
     *
     * @return void
     * @throws Exception If the specified file or directory does not exist.
     */
    private function handleRequest(): void
    {
        $navigationHandler = $this->renderer->navigationHandler();

        // Handle directory change requests
        if ($navigationHandler->isChangePathRequest()) {
            if ($this->filterBuilder) {
                $entries = FileStructureFilterer::filter(
                    $this->adapter->getFileStructure($navigationHandler->changeToPath())->getEntries(),
                    $this->filterBuilder
                );
            } else {
                $entries = $this->adapter->getFileStructure($navigationHandler->changeToPath());
            }
            $this->renderer->render(
                new RenderData(
                    $navigationHandler->changeToPath(),
                    $this->rootPath,
                    $entries
                )
            );
        }

        // Handle file download requests
        if ($navigationHandler->isDownloadFileRequest()) {
            $downloadFilePath = $navigationHandler->downloadFilePath();
            if ($this->adapter->fileOrDirectoryExists($downloadFilePath)) {
                $this->adapter->downloadFile($downloadFilePath);
            } else {
                throw new Exception("File [$downloadFilePath] does not exist.");
            }
        }

        // Handle default rendering of the root directory
        if (!$navigationHandler->isChangePathRequest() && !$navigationHandler->isDownloadFileRequest()) {
            if ($this->filterBuilder) {
                $entries = FileStructureFilterer::filter(
                    $this->adapter->getFileStructure($this->rootPath)->getEntries(),
                    $this->filterBuilder
                );
            } else {
                $entries = $this->adapter->getFileStructure($this->rootPath);
            }
            $this->renderer->render(
                new RenderData(
                    $this->rootPath,
                    $this->rootPath,
                    $entries
                )
            );
        }
    }
}
