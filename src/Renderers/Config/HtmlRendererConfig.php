<?php

namespace Marktaborosi\StorageNavigator\Renderers\Config;

use InvalidArgumentException;

/**
 * Class HtmlRendererConfig
 *
 * Configuration class for the HTML renderer in the FileBrowser.
 *
 * This class manages the configuration settings for the HTML renderer, allowing for merging
 * user-provided configurations with default settings. It validates the configuration to ensure
 * the settings adhere to expected formats and provides methods to retrieve and manage configuration values.
 *
 * @package Marktaborosi\StorageBrowser\Renderers\Config
 */
class HtmlRendererConfig
{
    /**
     * @var array The configuration settings for the HTML renderer.
     */
    private array $config;

    /**
     * Constructor to initialize the HtmlRendererConfig.
     *
     * Merges the provided configuration with the default configuration values.
     *
     * @param array $config Optional user-provided configuration settings. These settings will be merged with the default configuration.
     * @throws InvalidArgumentException If any of the configuration values are invalid.
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
     * Returns an array of default configuration settings used by the HTML renderer.
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
                default:
                    throw new InvalidArgumentException("Invalid configuration - Configuration key is invalid: [$configKey]");
            }
        }
    }

    /**
     * Validate the date format configuration setting.
     *
     * Ensures that `date_format` is a string.
     *
     * @param mixed $value The value to validate, expected to be a string representing the date format.
     * @throws InvalidArgumentException If `date_format` is not a string.
     */
    private function validateDateFormat(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException("Invalid configuration - date_format must be a string");
        }
    }
}
