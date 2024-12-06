<?php

namespace Marktaborosi\StorageNavigator\Interfaces;

use Marktaborosi\StorageNavigator\Renderers\Entities\RenderData;

/**
 * Interface StorageNavigatorRendererInterface
 *
 * Defines the contract for rendering the storage browser's output.
 * This interface provides methods for rendering the file browser
 * and handling navigation-related operations. Implementations of this
 * interface can render the browser in different formats (e.g., HTML, CLI).
 *
 * @package Marktaborosi\StorageBrowser\Interfaces
 * @pattern Strategy
 */
interface StorageNavigatorRendererInterface
{
    /**
     * Renders the file browser output.
     *
     * This method generates the visual or textual output for the file browser
     * based on the provided RenderData. The implementation can define how
     * the output is displayed (e.g., web interface, console).
     *
     * @param RenderData $data The data needed for rendering, including file structure,
     *                         configuration, and other relevant information.
     * @return void
     */
    public function render(RenderData $data): void;

    /**
     * Provides the navigation handler used to handle navigation-related operations.
     *
     * This method returns an instance of a navigation handler, which is responsible
     * for managing navigation requests such as changing directories, downloading files,
     * or handling other browser actions.
     *
     * @return StorageNavigatorActionHandlerInterface The navigation handler instance for managing navigation requests.
     */
    public function navigationHandler(): StorageNavigatorActionHandlerInterface;
}
