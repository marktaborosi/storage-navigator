<?php

use Marktaborosi\StorageNavigator\Adapters\SftpAdapter;
use Marktaborosi\StorageNavigator\Builders\FileStructureFilterBuilder;
use Marktaborosi\StorageNavigator\Renderers\Config\HtmlRendererConfig;
use Marktaborosi\StorageNavigator\Renderers\HtmlRenderer;
use Marktaborosi\StorageNavigator\StorageNavigator;

require_once '../vendor/autoload.php';

/**
 * This script initializes and configures the StorageNavigator application with:
 * - An SFTP adapter for accessing remote file systems securely.
 * - HTML rendering with a custom theme and configuration.
 * - Filtering options to display only files from the SFTP server.
 *
 * The StorageNavigator is then displayed in the browser, providing a user-friendly interface
 * for navigating and interacting with the SFTP server's file system.
 */

// Create an SFTP adapter for connecting to the SFTP server
$adapter = new SftpAdapter(
    'test.rebex.net',   // SFTP server address
    'demo',             // SFTP username
    'password',         // SFTP password
    22,                 // SFTP port (default 22)
    ""                  // Initial directory to browse
);

// Configure HTML renderer settings
$config = new HtmlRendererConfig([
    'date_format' => "Y-m-d H:i:s", // Define the date format for displaying timestamps
]);

// Create an HTML renderer with a custom theme and options
$renderer = new HtmlRenderer(
    themePath: "basic-norton", // Specify the theme path for styling
    config: $config, // Apply the renderer configuration
    disableNavigation: false, // Allow navigation between directories
    disableFileDownload: false // Enable file downloads
);

// Configure filtering options for the file structure (optional)
// Note: Additional filters can be applied using methods on $filterBuilder if needed
$filterBuilder = new FileStructureFilterBuilder();
$filterBuilder->isFile(); // Include only files in the displayed structure

// Initialize the StorageNavigator with the configured components
$browser = new StorageNavigator(
    adapter: $adapter, // Pass the SFTP adapter
    renderer: $renderer, // Pass the HTML renderer
    rootPath: "", // Set the root directory to browse
    filterBuilder: $filterBuilder, // Apply the configured file filter
);

// Display the file structure in the browser
$browser->display();
