<?php

namespace Marktaborosi\StorageNavigator\Filterer;

use Marktaborosi\StorageNavigator\Builders\FileStructureFilterBuilder;
use Marktaborosi\StorageNavigator\Entities\FileAttribute;
use Marktaborosi\StorageNavigator\Entities\DirectoryAttribute;
use Marktaborosi\StorageNavigator\Entities\FileStructure;

class FileStructureFilterer
{
    /**
     * Filters the given entries using the provided filters or FileStructureFilterBuilder.
     *
     * @param FileAttribute[]|DirectoryAttribute[] $entries The entries to filter.
     * @param FileStructureFilterBuilder|callable[] $filterBuilderOrFilters The filters to apply.
     * @return FileStructure The filtered FileStructure.
     */
    public static function filter(array $entries, FileStructureFilterBuilder|array $filterBuilderOrFilters): FileStructure
    {
        // Extract filters from the builder or use the provided array of filters
        $filters = $filterBuilderOrFilters instanceof FileStructureFilterBuilder
            ? $filterBuilderOrFilters->getFilters()
            : $filterBuilderOrFilters;

        // Apply filters
        foreach ($filters as $filter) {
            $entries = array_filter($entries, $filter);
        }

        return new FileStructure($entries);
    }
}
