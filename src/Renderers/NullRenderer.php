<?php

namespace Marktaborosi\StorageBrowser\Renderers;

use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserNavigationHandlerInterface;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserRendererInterface;
use Marktaborosi\StorageBrowser\Renderers\Entities\RenderData;
use Marktaborosi\StorageBrowser\Renderers\Navigators\NullNavigationHandler;

/**
 * Class NullRenderer
 *
 * A null renderer for file browsing.
 * This renderer implements the FileBrowserRendererInterface but performs no actual rendering.
 * It serves as a placeholder when no output is needed.
 */
class NullRenderer implements StorageBrowserRendererInterface
{
    /**
     * Renders the file browser output.
     *
     * This method does nothing and serves as a placeholder for rendering.
     *
     * @param RenderData $data Data needed for rendering, which is ignored by this renderer.
     * @return void
     */
    public function render(RenderData $data): void
    {
        // No rendering is performed
    }

    /**
     * Provides a navigation handler for the null renderer.
     *
     * @return StorageBrowserNavigationHandlerInterface The navigation handler instance, which is a NullNavigationHandler in this case.
     */
    public function navigationHandler(): StorageBrowserNavigationHandlerInterface
    {
        return new NullNavigationHandler();
    }
}
