<?php

use Marktaborosi\StorageBrowser\Adapters\ZipArchiveAdapter;
use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\Renderers\HtmlRenderer;
use Marktaborosi\StorageBrowser\StorageBrowser;

require_once '../vendor/autoload.php';

// Create zip archive adapter
try {
    $adapter = new ZipArchiveAdapter(__DIR__ . "/storage/zips/1mb-fake-sample.zip");
} catch (Exception $e) {
    die($e);
}

// Create renderer
$renderer = new HtmlRenderer(theme: 'console-norton-commander');

// Create config
$config = new FileBrowserConfig([
]);

// Create Browser
try {
    $browser = new StorageBrowser(
        adapter: $adapter,
        renderer: $renderer,
        config: $config,
        rootPath: ""
    );
    // Render Browser
    $browser->display();
} catch (Exception $e) {
    die($e);
}


