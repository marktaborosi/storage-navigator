<?php

namespace Marktaborosi\StorageNavigator\Builders;

use Marktaborosi\StorageNavigator\Entities\FileAttribute;
use Marktaborosi\StorageNavigator\Entities\DirectoryAttribute;

/**
 * Class FileStructureFilterBuilder
 *
 * A builder for creating filters to navigate and filter file and directory structures.
 */
class FileStructureFilterBuilder
{
    /**
     * @var callable[] List of filter criteria.
     */
    private array $filters = [];

    /**
     * @var array Temporary stack for handling grouped filters.
     */
    private array $groupStack = [];

    /**
     * @var string|null The current logical operator for combining filters ('AND' or 'OR').
     */
    private ?string $currentLogic = 'AND';

    /**
     * Normalize the input to an array if it is not already one.
     *
     * @param string|array $input The input to normalize.
     * @return array The normalized array.
     */
    private function normalizeInput(string|array $input): array
    {
        return is_array($input) ? $input : [$input];
    }

    /**
     * Add a filter to the current group or the main filter list.
     *
     * @param callable $filter The filter function to add.
     * @return void
     */
    private function addFilter(callable $filter): void
    {
        if (!empty($this->groupStack)) {
            $currentGroup = &$this->groupStack[count($this->groupStack) - 1];
            $currentGroup[] = $filter;
        } else {
            $this->filters[] = $filter;
        }
    }

    /**
     * Adds a filter for files only.
     *
     * @return $this
     */
    public function isFile(): self
    {
        $this->addFilter(function ($entry) {
            return $entry instanceof FileAttribute;
        });

        return $this;
    }

    /**
     * Adds a filter for directories only.
     *
     * @return $this
     */
    public function isDirectory(): self
    {
        $this->addFilter(function ($entry) {
            return $entry instanceof DirectoryAttribute;
        });

        return $this;
    }

    /**
     * Adds a filter for names (files or directories) that equal any of the given values.
     *
     * @param string|array $filenames The name(s) to match.
     * @return $this
     */
    public function nameEquals(string|array $filenames): self
    {
        $filenames = $this->normalizeInput($filenames);
        $this->addFilter(function ($entry) use ($filenames) {
            if ($entry instanceof FileAttribute) {
                return in_array($entry->getFilename(), $filenames, true);
            } elseif ($entry instanceof DirectoryAttribute) {
                return in_array($entry->getName(), $filenames, true);
            }
            return false;
        });

        return $this;
    }

    /**
     * Adds a filter for names (files or directories) that do not equal any of the given values.
     *
     * @param string|array $filenames The name(s) to exclude.
     * @return $this
     */
    public function nameNotEquals(string|array $filenames): self
    {
        $filenames = $this->normalizeInput($filenames);
        $this->filters[] = function ($entry) use ($filenames) {
            if ($entry instanceof FileAttribute) {
                return !in_array($entry->getFilename(), $filenames, true);
            } elseif ($entry instanceof DirectoryAttribute) {
                return !in_array($entry->getName(), $filenames, true);
            }
            return false;
        };

        return $this;
    }

    /**
     * Adds a filter for names (files or directories) that contain any of the given substrings.
     *
     * @param string|array $substrings The substring(s) to search for.
     * @return $this
     */
    public function nameContains(string|array $substrings): self
    {
        $substrings = $this->normalizeInput($substrings);
        $this->filters[] = function ($entry) use ($substrings) {
            if ($entry instanceof FileAttribute) {
                return !empty(array_filter($substrings, fn($substring) => str_contains($entry->getFilename(), $substring)));
            } elseif ($entry instanceof DirectoryAttribute) {
                return !empty(array_filter($substrings, fn($substring) => str_contains($entry->getName(), $substring)));
            }
            return false;
        };

        return $this;
    }

    /**
     * Adds a filter for names (files or directories) that do not contain any of the given substrings.
     *
     * @param string|array $substrings The substring(s) to exclude.
     * @return $this
     */
    public function nameNotContains(string|array $substrings): self
    {
        $substrings = $this->normalizeInput($substrings);
        $this->filters[] = function ($entry) use ($substrings) {
            if ($entry instanceof FileAttribute) {
                return !array_filter($substrings, fn($substring) => str_contains($entry->getFilename(), $substring));
            } elseif ($entry instanceof DirectoryAttribute) {
                return !array_filter($substrings, fn($substring) => str_contains($entry->getName(), $substring));
            }
            return false;
        };

        return $this;
    }

    /**
     * Adds a filter for file extensions that equal any of the given values.
     *
     * @param string|array $extensions The file extension(s) to match.
     * @return $this
     */
    public function extensionEquals(string|array $extensions): self
    {
        $extensions = $this->normalizeInput($extensions);
        $this->filters[] = function ($entry) use ($extensions) {
            return $entry instanceof FileAttribute && in_array($entry->getExtension(), $extensions, true);
        };

        return $this;
    }

    /**
     * Adds a filter for file extensions that do not equal any of the given values.
     *
     * @param string|array $extensions The file extension(s) to exclude.
     * @return $this
     */
    public function extensionNotEquals(string|array $extensions): self
    {
        $extensions = $this->normalizeInput($extensions);
        $this->filters[] = function ($entry) use ($extensions) {
            return $entry instanceof FileAttribute && !in_array($entry->getExtension(), $extensions, true);
        };

        return $this;
    }

    /**
     * Adds a filter for file extensions that contain any of the given substrings.
     *
     * @param string|array $substrings The substring(s) to search for in extensions.
     * @return $this
     */
    public function extensionContains(string|array $substrings): self
    {
        $substrings = $this->normalizeInput($substrings);
        $this->filters[] = function ($entry) use ($substrings) {
            return $entry instanceof FileAttribute && array_filter($substrings, fn($substring) => str_contains($entry->getExtension(), $substring));
        };

        return $this;
    }

    /**
     * Adds a filter for file extensions that do not contain any of the given substrings.
     *
     * @param string|array $substrings The substring(s) to exclude from extensions.
     * @return $this
     */
    public function extensionNotContains(string|array $substrings): self
    {
        $substrings = $this->normalizeInput($substrings);
        $this->filters[] = function ($entry) use ($substrings) {
            return $entry instanceof FileAttribute && !array_filter($substrings, fn($substring) => str_contains($entry->getExtension(), $substring));
        };

        return $this;
    }

    /**
     * Get the list of filters.
     *
     * @return callable[] The list of filter criteria.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
