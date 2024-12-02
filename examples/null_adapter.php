<?php

use Marktaborosi\StorageBrowser\Adapters\NullAdapter;
use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\StorageBrowser;
use Marktaborosi\StorageBrowser\Renderers\NullRenderer;

require_once '../vendor/autoload.php';

// Create native adapter
$adapter = new NullAdapter();

// Create renderer
$renderer = new NullRenderer();

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

}
