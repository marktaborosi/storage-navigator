<?php

use Marktaborosi\StorageNavigator\Adapters\PHPNativeAdapter;
use Marktaborosi\StorageNavigator\Builders\FileStructureFilterBuilder;
use Marktaborosi\StorageNavigator\Renderers\Config\HtmlRendererConfig;
use Marktaborosi\StorageNavigator\Renderers\HtmlRenderer;
use Marktaborosi\StorageNavigator\StorageNavigator;

require_once '../vendor/autoload.php';

/**
 * This script initializes and configures the StorageNavigator application with:
 * - A PHP native filesystem adapter.
 * - HTML rendering with a custom theme and configuration.
 * - Filtering options to display only files in the specified root directory.
 *
 * The StorageNavigator is then displayed in the browser.
 */

// Create a native PHP adapter for filesystem operations
$adapter = new PHPNativeAdapter();

// Configure HTML renderer settings
$config = new HtmlRendererConfig([
    'date_format' => "Y-m-d H:i:s", // Set the format for displaying timestamps
]);

// Create an HTML renderer with a custom theme and options
$renderer = new HtmlRenderer(
    themePath: "basic-mac", // Set the theme path for styling
    config: $config, // Pass the renderer configuration
    disableNavigation: false, // Enable directory navigation
    disableFileDownload: false, // Allow file downloads
);

// Configure a filter to display only files
// Note: Additional filters can be applied using methods on $filterBuilder if needed
$filterBuilder = new FileStructureFilterBuilder();
$filterBuilder
    ->isFile(); // Filter entries to include only files

// Initialize the StorageNavigator with the configured components
$browser = new StorageNavigator(
    adapter: $adapter, // Pass the PHP native filesystem adapter
    renderer: $renderer, // Pass the HTML renderer
    rootPath: __DIR__ . "/storage/", // Set the root directory to browse
    filterBuilder: $filterBuilder, // Apply the configured file filter
);

// Display the file structure in the browser
$browser->display();
