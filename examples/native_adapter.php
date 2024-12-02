<?php

use Marktaborosi\StorageBrowser\Adapters\PHPNativeAdapter;
use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\Renderers\HtmlRenderer;
use Marktaborosi\StorageBrowser\StorageBrowser;

require_once '../vendor/autoload.php';

// Create native adapter
$adapter = new PHPNativeAdapter();

// Create config
$config = new FileBrowserConfig([
    'date_format' => "Y-m-d H:i:s",
    'ignore_filenames' => [],
    'ignore_extensions' => []
]);

// Create renderer
$renderer = new HtmlRenderer(
    theme: "console-norton-commander",
    disableNavigation: false,
    disableFileDownload: false
);


// Create Browser
try {
    $browser = new StorageBrowser(
        adapter: $adapter,
        renderer: $renderer,
        config: $config,
        rootPath: __DIR__ . "/storage/"
    );

    $browser->display();
} catch (Exception $e) {
    die($e);
}


