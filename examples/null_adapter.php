<?php

use Marktaborosi\StorageNavigator\Adapters\NullAdapter;
use Marktaborosi\StorageNavigator\Renderers\Config\HtmlRendererConfig;
use Marktaborosi\StorageNavigator\Renderers\NullRenderer;
use Marktaborosi\StorageNavigator\StorageNavigator;

require_once '../vendor/autoload.php';

/**
 * Example script for initializing the Storage Navigator with a NullAdapter and NullRenderer.
 * Null Adapter and Renderer does nothing
 */

// Create a NullAdapter instance.
// The NullAdapter is a placeholder that does not perform any storage operations.
$adapter = new NullAdapter();

/**
 * Create a NullRenderer instance.
 *
 * The NullRenderer is a placeholder renderer that does not produce any output.
 * It is commonly used for debugging or scenarios where no rendering is required.
 */
$renderer = new NullRenderer();

//Initialize the StorageNavigator with the following:
$browser = new StorageNavigator(
    adapter: $adapter,
    renderer: $renderer,
    rootPath: "",
    filterBuilder: null
);
