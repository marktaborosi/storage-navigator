<?php

declare(strict_types=1);

use Marktaborosi\StorageNavigator\StorageNavigator;
use Marktaborosi\StorageNavigator\Storage\Storage;

require __DIR__ . '/vendor/autoload.php';

// Browse the shared folder, read-only.
$navigator = new StorageNavigator(Storage::local('/srv/shared'));
$navigator->handleGlobals();
