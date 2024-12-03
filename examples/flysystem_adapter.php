<?php

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\StorageBrowser\Adapters\FlysystemAdapter;
use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\Renderers\HtmlRenderer;
use Marktaborosi\StorageBrowser\StorageBrowser;

require_once '../vendor/autoload.php';

// Define filesystem
$localAdapter = new LocalFilesystemAdapter(__DIR__."/storage/");
$filesystem = new Filesystem($localAdapter);

// Create filesystem adapter
$adapter = new FlysystemAdapter($filesystem);

// Create renderer
$renderer = new HtmlRenderer(themePath: 'console-midnight-commander');

// Create config
$config = new FileBrowserConfig([
    'date_format' => "M d Y H:i",
    'ignore_filenames' => ["file.log"], // Exclude file.log
    'ignore_extensions' => ['zip','php','html'] // Excludes extensions
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


