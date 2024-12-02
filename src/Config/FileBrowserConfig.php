<?php

namespace Marktaborosi\StorageBrowser\Config;

use InvalidArgumentException;

/**
 * Configuration class for the FileBrowser.
 *
 * This class manages the configuration settings for the FileBrowser, allowing for
 * merging of user-provided configuration with default settings. It validates the
 * configuration settings to ensure they adhere to expected formats.
 */
class FileBrowserConfig
{
    /**
     * @var array The configuration settings for the FileBrowser.
     */
    private array $config;

    /**
     * @var array List of valid configuration keys.
     */
    const CONFIGURATION_KEYS = ['date_format', 'ignore_filenames', 'ignore_extensions', 'disable_file_download', 'disable_navigation'];

    /**
     * Constructor to initialize the FileBrowserConfig.
     *
     * Merges the provided configuration with the default configuration.
     *
     * @param array $config Optional user-provided configuration settings. These settings will be merged with the default configuration.
     */
    public function __construct(array $config = [])
    {
        $this->validateConfiguration($config);

        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Retrieve a configuration value by its key.
     *
     * If the key does not exist in the configuration, the provided default value is returned.
     *
     * @param string $key The configuration key to retrieve.
     * @param mixed|null $default The default value to return if the key is not found. Default is null.
     * @return mixed The configuration value associated with the key, or the default value if the key does not exist.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Get the default configuration settings.
     *
     * This method returns an array of default configuration settings used by the FileBrowser.
     *
     * @return array The default configuration settings.
     */
    private function getDefaultConfig(): array
    {
        return [
            'date_format' => 'M d Y H:i',
            'ignore_filenames' => [],
            'ignore_extensions' => []
        ];
    }

    /**
     * Validate the user-provided configuration settings.
     *
     * Checks for invalid date formats, filenames, and extensions, throwing an exception if any are found.
     *
     * @param array $config The user-provided configuration settings to validate.
     * @throws InvalidArgumentException If any configuration values are invalid.
     */
    private function validateConfiguration(array $config): void
    {
        foreach ($config as $configKey => $value) {
            switch ($configKey) {
                case 'date_format':
                    $this->validateDateFormat($value);
                    break;
                case 'ignore_filenames':
                    $this->validateIgnoreFilenames($value);
                    break;
                case 'ignore_extensions':
                    $this->validateIgnoreExtensions($value);
                    break;
                default:
                    throw new InvalidArgumentException("Invalid configuration - Configuration key is invalid: [$configKey]");
            }
        }
    }

    /**
     * Validate the ignore extensions configuration setting.
     *
     * Ensures that ignore_extensions is an array and contains only valid extensions.
     *
     * @param mixed $value The value to validate, expected to be an array of extensions.
     * @throws InvalidArgumentException If ignore_extensions is not an array or contains invalid extensions.
     */
    private function validateIgnoreExtensions(mixed $value): void
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException("Invalid configuration - ignore_extensions must be an array");
        }
        $invalidExtensions = $this->getInvalidExtensions($value);
        if ($invalidExtensions) {
            $invalidExtensions = implode(", ", $invalidExtensions);
            throw new InvalidArgumentException("Invalid configuration - [$invalidExtensions] containing invalid characters in ignore_extensions");
        }
    }

    /**
     * Validate the ignore filenames configuration setting.
     *
     * Ensures that ignore_filenames is an array and contains only valid filenames.
     *
     * @param mixed $value The value to validate, expected to be an array of filenames.
     * @throws InvalidArgumentException If ignore_filenames is not an array or contains invalid filenames.
     */
    private function validateIgnoreFilenames(mixed $value): void
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException("Invalid configuration - ignore_filenames must be an array");
        }
        $invalidFilenames = $this->getInvalidFilenames($value);
        if ($invalidFilenames) {
            $invalidFilenames = implode(", ", $invalidFilenames);
            throw new InvalidArgumentException("Invalid configuration - [$invalidFilenames] containing invalid characters in ignore_filenames");
        }
    }

    /**
     * Validate the date format configuration setting.
     *
     * Ensures that date_format is a string.
     *
     * @param mixed $value The value to validate, expected to be a string representing the date format.
     * @throws InvalidArgumentException If date_format is not a string.
     */
    private function validateDateFormat(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException("Invalid configuration - date_format must be a string");
        }
    }

    /**
     * Get invalid filenames from the provided list.
     *
     * Checks each filename for invalid characters and returns an array of those that are invalid.
     *
     * @param array $filenames The list of filenames to validate.
     * @return array An array of invalid filenames.
     */
    private function getInvalidFilenames(array $filenames): array
    {
        // Define a regex pattern to match invalid characters
        $invalidCharPattern = '/[\/:*?"<>|\\\]/';

        // Array to hold invalid filenames
        $invalidFilenames = [];

        // Iterate over each filename
        foreach ($filenames as $filename) {
            // Check if filename contains invalid characters
            if (preg_match($invalidCharPattern, $filename) || empty($filename)) {
                // Add to invalid filenames array
                $invalidFilenames[] = $filename;
            }
        }

        return $invalidFilenames;
    }

    /**
     * Get invalid extensions from the provided list.
     *
     * Checks each extension for validity based on a regex pattern and returns an array of those that are invalid.
     *
     * @param array $extensions The list of extensions to validate.
     * @return array An array of invalid extensions.
     */
    private function getInvalidExtensions(array $extensions): array
    {
        // Define a regex pattern to match valid extensions (alphanumeric and periods only)
        $validExtensionPattern = '/^[a-zA-Z0-9]+(?:\.[a-zA-Z0-9]+)*$/';

        // Array to hold invalid extensions
        $invalidExtensions = [];

        // Iterate over each extension
        foreach ($extensions as $extension) {
            // Check if the extension is valid based on the regex pattern
            if (!preg_match($validExtensionPattern, $extension)) {
                // Add to invalid extensions array
                $invalidExtensions[] = $extension;
            }
        }

        return $invalidExtensions;
    }
}
