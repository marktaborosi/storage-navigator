<?php

use Marktaborosi\StorageNavigator\Adapters\FTP\FtpConnection;
use Marktaborosi\StorageNavigator\Adapters\FtpAdapter;
use Marktaborosi\StorageNavigator\Builders\FileStructureFilterBuilder;
use Marktaborosi\StorageNavigator\Renderers\Config\HtmlRendererConfig;
use Marktaborosi\StorageNavigator\Renderers\HtmlRenderer;
use Marktaborosi\StorageNavigator\StorageNavigator;

require_once '../vendor/autoload.php';

/**
 * This script initializes and configures the StorageNavigator application with:
 * - An FTP connection for remote file access.
 * - HTML rendering with a custom theme and configuration.
 * - Optional filtering options for the file structure.
 *
 * The StorageNavigator is then displayed in the browser, allowing users to navigate and interact with
 * the FTP server's file system.
 */

// Create an FTP adapter for connecting to the FTP server
$adapter = new FtpAdapter(new FtpConnection());
$adapter->initialize(
    'test.rebex.net', // FTP server host
    'demo', // Username for authentication
    'password', // Password for authentication
    21, // FTP server port
    "/" // Initial directory path on the FTP server
);

// Configure HTML renderer settings
$config = new HtmlRendererConfig([
    'date_format' => "Y-m-d H:i:s", // Set the format for displaying file timestamps
]);

// Create an HTML renderer with a custom theme and options
$renderer = new HtmlRenderer(
    themePath: "basic-norton", // Set the theme path for styling
    config: $config, // Pass the renderer configuration
    disableNavigation: false, // Enable directory navigation
    disableFileDownload: false // Allow file downloads
);

// Configure filtering options for the file structure (optional)
// Note: Additional filters can be applied using methods on $filterBuilder if needed
$filterBuilder = new FileStructureFilterBuilder();
$filterBuilder->isFile(); // Shows only files

// Initialize the StorageNavigator with the configured components
$browser = new StorageNavigator(
    adapter: $adapter, // Pass the FTP adapter
    renderer: $renderer, // Pass the HTML renderer
    rootPath: "", // Set the root directory to browse
    filterBuilder: $filterBuilder, // Apply the configured file filter
);

// Display the file structure in the browser
$browser->display();
