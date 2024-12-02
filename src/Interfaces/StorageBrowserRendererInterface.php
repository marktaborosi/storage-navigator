<?php

namespace Marktaborosi\StorageBrowser\Interfaces;

use Marktaborosi\StorageBrowser\Renderers\Entities\RenderData;

/**
 * Interface StorageBrowserRendererInterface
 *
 * Defines the methods required for rendering the storage browser output.
 *
 * @pattern Strategy
 *
 */
interface StorageBrowserRendererInterface
{
    /**
     * Renders the file browser output.
     *
     * @param RenderData $data The data needed for rendering, including file structure, configuration, and other relevant information.
     * @return void
     */
    public function render(RenderData $data): void;

    /**
     * Provides the navigation handler used to handle navigation-related operations.
     *
     * @return StorageBrowserNavigationHandlerInterface The navigation handler instance for managing navigation requests.
     */
    public function navigationHandler(): StorageBrowserNavigationHandlerInterface;
}
