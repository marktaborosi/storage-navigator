<?php

namespace Marktaborosi\StorageNavigator\Renderers;

use Marktaborosi\StorageNavigator\Interfaces\StorageNavigatorNavigationHandlerInterface;
use Marktaborosi\StorageNavigator\Interfaces\StorageNavigatorRendererInterface;
use Marktaborosi\StorageNavigator\Renderers\Entities\RenderData;
use Marktaborosi\StorageNavigator\Renderers\Navigators\NullNavigationHandler;

/**
 * Class NullRenderer
 *
 * A null renderer for file browsing.
 * This renderer implements the FileBrowserRendererInterface but performs no actual rendering.
 * It serves as a placeholder when no output is needed.
 */
class NullRenderer implements StorageNavigatorRendererInterface
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
     * @return StorageNavigatorNavigationHandlerInterface The navigation handler instance, which is a NullNavigationHandler in this case.
     */
    public function navigationHandler(): StorageNavigatorNavigationHandlerInterface
    {
        return new NullNavigationHandler();
    }
}
