<?php

use Marktaborosi\StorageNavigator\Builders\FileStructureFilterBuilder;
use Marktaborosi\StorageNavigator\Renderers\Config\HtmlRendererConfig;
use Marktaborosi\StorageNavigator\Renderers\HtmlRenderer;
use Marktaborosi\StorageNavigator\StorageNavigator;

require_once '../vendor/autoload.php';

/**
 * This script initializes and configures the StorageNavigator application with:
 * - S3 Storage adapter.
 * The StorageNavigator is then displayed in the browser.
 */

// Create  (You can use the S3 Adapter which works for both Google and AWS)
// You can use here GoogleCloudStorageAdapter() / AwsCloudStorageAdapter() if you want it more readable
$s3Client = new \Aws\S3\S3Client([
    'region' => 'us-east-1',
    'version' => 'latest',
    'endpoint' => 'https://your-endpoint.com',
    'credentials' => [
        'key' => 'your-client-key',
        'secret' => 'your-client-secret',
    ],
    // 'use_path_style_endpoint' => true,  // If MinIo is used, this is necessary
]);

$adapter = new \Marktaborosi\StorageNavigator\Adapters\AwsCloudStorageAdapter(
    'test-bucket',
    $s3Client

);

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

// Initialize the StorageNavigator with the configured components
$navigator = new StorageNavigator(
    adapter: $adapter, // Pass the PHP native filesystem adapter
    renderer: $renderer, // Pass the HTML renderer
    rootPath: "", // Set the root directory to browse
    filterBuilder: $filterBuilder, // Apply the configured file filter
);

// Display the file structure in the browser
$navigator->display();
