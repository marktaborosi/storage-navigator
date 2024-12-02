<?php

namespace Marktaborosi\StorageBrowser\Renderers;

use Exception;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserNavigationHandlerInterface;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserRendererInterface;
use Marktaborosi\StorageBrowser\Renderers\Entities\RenderData;
use Marktaborosi\StorageBrowser\Renderers\Navigators\NullNavigationHandler;

/**
 * Class ConsoleRenderer
 *
 * A renderer that outputs the file browser's structure and details to the console,
 * mimicking common terminal commands like 'ls -la' on Unix systems or 'dir' on Windows.
 * This class provides a text-based view suitable for CLI environments.
 *
 * @package Marktaborosi\StorageBrowser\Renderers
 * @pattern Strategy
 */
class ConsoleRenderer implements StorageBrowserRendererInterface
{
    /**
     * Renders the file browser output in the console, similar to 'ls -la' in Ubuntu or 'dir' in Windows.
     *
     * This method ensures that the script is executed in a CLI environment before
     * formatting and outputting the file and directory structure to the console.
     *
     * @param RenderData $data The data needed for rendering, including the current path, files, and configuration.
     * @return void
     * @throws Exception If not running in a CLI environment.
     */
    public function render(RenderData $data): void
    {
        // Ensure that the script is being run in CLI mode
        if (php_sapi_name() !== 'cli') {
            throw new Exception("Not running in CLI mode");
        }

        $output = [];
        $output[] = "Location: {$data->getCurrentPath()}";
        $output[] = str_pad("Date", 20) . str_pad("Time", 8) . str_pad("", 14) . "Name";
        $output[] = str_repeat("-", 60);

        foreach ($data->getStructure()->toArray() as $file) {
            // Retrieve file attributes with default values if missing
            $lastModified = $file['last_modified'] ?? null;
            $date = $lastModified ? date("m/d/Y", strtotime($lastModified)) : '';
            $time = $lastModified ? date("h:i A", strtotime($lastModified)) : '';

            // Format the file size or indicate a directory
            $size = $file['type'] === "dir" ? "<DIR>" : number_format($file['size']['value'] ?? 0);

            // Determine the file name
            $fileName = $file['type'] === 'file' ? $file['filename'] : $file['name'];

            // Prepare the line to output
            $output[] = str_pad($date, 12) . " " . str_pad($time, 12) . " " . str_pad($size, 16) . " " . $fileName;
        }

        // Output everything at once
        echo implode("\n", $output) . "\n";
    }

    /**
     * Provides a navigation handler for the file browser.
     *
     * Since this renderer is for the console and no navigation is required,
     * a NullNavigationHandler is returned.
     *
     * @return StorageBrowserNavigationHandlerInterface Returns a NullNavigationHandler, as no navigation is required in the console.
     */
    public function navigationHandler(): StorageBrowserNavigationHandlerInterface
    {
        return new NullNavigationHandler();
    }
}
