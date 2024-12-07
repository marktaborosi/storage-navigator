<?php

namespace Marktaborosi\StorageNavigator\Tests\Traits;

trait DummyFilesTrait
{


    /**
     * The root storage location used for file and directory operations.
     */
    const TEST_FILES_STORAGE_LOCATION = __DIR__. DIRECTORY_SEPARATOR."..". DIRECTORY_SEPARATOR. 'Files'. DIRECTORY_SEPARATOR;

    /**
     * Get the absolute path to the test files storage directory.
     *
     * @return string The absolute path to the test files storage directory.
     */
    public static function dummyFilesStoragePaths(): string
    {
        return realpath(self::TEST_FILES_STORAGE_LOCATION) . DIRECTORY_SEPARATOR;
    }

}