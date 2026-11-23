<?php

/**
 * Storage Navigator demo.
 *
 *   composer install
 *   php -S localhost:8000 -t examples/demo
 *
 * Then open http://localhost:8000. Pick another theme or language with environment variables:
 *
 *   SN_THEME=norton-commander SN_LOCALE=hu php -S localhost:8000 -t examples/demo
 *
 * The demo works on a copy of examples/demo/files in the system temp directory, so
 * uploads and deletes never touch the repository. Do not expose it on a network:
 * it enables every write feature for anyone.
 */

declare(strict_types=1);

use Marktaborosi\StorageNavigator\Features;
use Marktaborosi\StorageNavigator\Options;
use Marktaborosi\StorageNavigator\Rendering\Theme;
use Marktaborosi\StorageNavigator\Storage\Storage;
use Marktaborosi\StorageNavigator\StorageNavigator;

require __DIR__ . '/../../vendor/autoload.php';

$source = getenv('SN_DEMO_FILES') ?: __DIR__ . '/files';
$workdir = sys_get_temp_dir() . '/storage-navigator-demo-' . substr(md5($source), 0, 8);
if (!is_dir($workdir)) {
    $copy = static function (string $from, string $to) use (&$copy): void {
        mkdir($to, 0777, true);
        foreach (new DirectoryIterator($from) as $item) {
            if ($item->isDot()) {
                continue;
            }
            $target = $to . '/' . $item->getFilename();
            $item->isDir() ? $copy($item->getPathname(), $target) : copy($item->getPathname(), $target);
            touch($target, $item->getMTime());
        }
    };
    $copy($source, $workdir);
}

$theme = getenv('SN_THEME') ?: 'modern';

$navigator = new StorageNavigator(
    Storage::local($workdir),
    new Options(
        title: 'Demo files',
        theme: in_array($theme, Theme::names(), true) ? $theme : 'modern',
        locale: getenv('SN_LOCALE') ?: 'en',
        features: class_exists(\ZipStream\ZipStream::class) ? Features::all() : new Features(upload: true, createDirectory: true, rename: true, delete: true),
        // Demo only. In a real application keep a random secret in your configuration.
        csrfSecret: hash('sha256', __FILE__ . php_uname()),
    ),
);

$navigator->handleGlobals();
