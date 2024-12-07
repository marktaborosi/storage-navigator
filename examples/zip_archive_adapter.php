<?php

use Marktaborosi\StorageNavigator\Adapters\ZipArchiveAdapter;
use Marktaborosi\StorageNavigator\Builders\FileStructureFilterBuilder;
use Marktaborosi\StorageNavigator\Renderers\Config\HtmlRendererConfig;
use Marktaborosi\StorageNavigator\Renderers\HtmlRenderer;
use Marktaborosi\StorageNavigator\StorageNavigator;

require_once '../vendor/autoload.php';

/**
 * This script configures and displays the contents of a ZIP archive using the StorageNavigator application.
 *
 * - A `ZipArchiveAdapter` is used to interact with the archive file.
 * - An `HtmlRenderer` is configured with a custom theme and settings to render the output.
 * - Filtering is applied to include only files in the displayed structure.
 * - The StorageNavigator is displayed in the browser, allowing navigation and interaction with the archive contents.
 */

// Create a ZIP archive adapter to interact with the specified archive
try {
    $adapter = new ZipArchiveAdapter(__DIR__ . "/storage/zips/1mb-fake-sample.zip");
} catch (Exception $e) {
    die($e); // Terminate execution if the ZIP archive cannot be accessed
}

// Configure HTML renderer settings
$config = new HtmlRendererConfig([
    'date_format' => "Y-m-d H:i:s", // Define the format for displaying timestamps
]);

// Create an HTML renderer with a custom theme and options
$renderer = new HtmlRenderer(
    themePath: "basic-mac", // Specify the theme path for styling
    config: $config, // Apply the renderer configuration
    disableNavigation: false, // Allow directory navigation
    disableFileDownload: false // Enable file downloads
);

// Configure a filter to display only files
// Additional filters can be applied using methods on $filterBuilder if needed
$filterBuilder = new FileStructureFilterBuilder();
$filterBuilder
    ->isFile(); // Include only files in the displayed structure

// Initialize the StorageNavigator with the configured components
// Note: Additional filters can be applied using methods on $filterBuilder if needed
$browser = new StorageNavigator(
    adapter: $adapter, // Pass the archive adapter
    renderer: $renderer, // Pass the HTML renderer
    rootPath: "", // Set the root directory to browse
    filterBuilder: $filterBuilder, // Apply the configured file filter
);

// Display the file structure in the browser
$browser->display();
