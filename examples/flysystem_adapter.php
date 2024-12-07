<?php

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\StorageNavigator\Adapters\FlysystemAdapter;
use Marktaborosi\StorageNavigator\Builders\FileStructureFilterBuilder;
use Marktaborosi\StorageNavigator\Renderers\Config\HtmlRendererConfig;
use Marktaborosi\StorageNavigator\Renderers\HtmlRenderer;
use Marktaborosi\StorageNavigator\StorageNavigator;

require_once '../vendor/autoload.php';

/**
 * This script initializes and configures the StorageNavigator application with a local filesystem,
 * HTML rendering capabilities, and filtering options.
 *
 * - The local filesystem is configured as the storage backend.
 * - An HTML renderer is used for displaying the file structure.
 * - Filtering is applied to show only directories containing the string "MyName" in their names.
 * - The StorageNavigator is then displayed in the browser.
 */

// Define the local filesystem adapter for the storage directory
$localAdapter = new LocalFilesystemAdapter(__DIR__ . "/storage/");
$filesystem = new Filesystem($localAdapter);

// Create a Flysystem adapter for use with StorageNavigator
$adapter = new FlysystemAdapter($filesystem);

// Configure the HTML renderer with specific settings
$config = new HtmlRendererConfig([
    'date_format' => "M d Y H:i", // Define date format for file timestamps
]);

// Create the HTML renderer with a specific theme and options
$renderer = new HtmlRenderer(
    themePath: 'basic-light', // Set theme path
    config: $config, // Pass configuration
    disableNavigation: false, // Enable navigation
    disableFileDownload: false // Enable file downloads
);

// Configure filtering options for the file structure (optional)
$filterBuilder = new FileStructureFilterBuilder();
$filterBuilder
    ->isFile() // Filter to include only file entries
    ->nameContains('document.pdf'); // Match files containing the specified names

// Initialize the StorageNavigator with configured components
$navigator = new StorageNavigator(
    adapter: $adapter, // Pass the filesystem adapter
    renderer: $renderer, // Pass the HTML renderer
    rootPath: "", // Set the root path for the navigator
    filterBuilder: $filterBuilder, // Apply the configured filter builder
);

// Display the file structure in the browser
$navigator->display();
