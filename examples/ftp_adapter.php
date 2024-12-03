<?php

use Marktaborosi\StorageBrowser\Adapters\FTP\FtpConnection;
use Marktaborosi\StorageBrowser\Adapters\FtpAdapter;
use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\Renderers\HtmlRenderer;
use Marktaborosi\StorageBrowser\StorageBrowser;

require_once '../vendor/autoload.php';

// Create ftp adapter
try {
    $adapter = new FtpAdapter(new FtpConnection());

    $adapter->initialize(
        'test.rebex.net',
        'demo',
        'password',
        21,
        "/"
    );
} catch (Exception $e) {
    die($e);
}



// Create renderer
$renderer = new HtmlRenderer(themePath: 'console-norton-commander');

// Create config
$config = new FileBrowserConfig([
    'ignore_filenames' => [],
    'ignore_extensions' => [],
]);


// Create Browser
try {
    $browser = new StorageBrowser(
        adapter: $adapter,
        renderer: $renderer,
        config: $config,
        rootPath: ""
    );

    $browser->display();
} catch (Exception $e) {
    die($e);
}


