<?php

namespace Marktaborosi\StorageNavigator\Tests\Unit\Renderers\Config;

use InvalidArgumentException;
use Marktaborosi\StorageNavigator\Renderers\Config\HtmlRendererConfig;
use PHPUnit\Framework\TestCase;

/**
 * Class StorageNavigatorConfigTest
 *
 * This class tests the functionality of the `StorageBrowserConfig` class,
 * ensuring proper behavior when configurations are provided, validated,
 * and retrieved.
 *
 * @package Marktaborosi\Tests\Unit\Config
 */
class HtmlRendererConfigTest extends TestCase
{
    /**
     * Tests that the constructor merges the provided configuration with the default configuration.
     *
     * This test verifies that any custom configuration values provided to the constructor
     * are correctly merged with the default configuration values.
     */
    public function test_constructor_merges_config_with_defaults(): void
    {
        $customConfig = [
            'date_format' => 'Y-m-d',
        ];

        $config = new HtmlRendererConfig($customConfig);

        $this->assertEquals('Y-m-d', $config->get('date_format'));
    }

    /**
     * Tests that the constructor throws an exception for an invalid configuration key.
     *
     * This test ensures that an exception is raised if the constructor receives a configuration
     * key that is not recognized.
     */
    public function test_constructor_throws_exception_for_invalid_config_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration - Configuration key is invalid: [invalid_key]');

        new HtmlRendererConfig(['invalid_key' => 'value']);
    }

    /**
     * Tests that the `get()` method returns a default value if the requested key does not exist.
     *
     * This test ensures that the `get()` method correctly falls back to the default value
     * when the requested configuration key is missing.
     */
    public function test_get_returns_default_value_if_key_does_not_exist(): void
    {
        $config = new HtmlRendererConfig();

        $this->assertEquals('default_value', $config->get('non_existent_key', 'default_value'));
    }

    /**
     * Tests that an exception is thrown when `date_format` is not a string.
     *
     * This test ensures that the `StorageNavigatorConfig` constructor validates the `date_format`
     * field and throws an exception if it is not a string.
     */
    public function test_constructor_throws_exception_for_invalid_date_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration - date_format must be a string');

        new HtmlRendererConfig(['date_format' => 12345]);
    }

    /**
     * Tests that `getDefaultConfig()` returns the correct default configuration values.
     *
     * This test verifies that the default configuration values are correctly returned
     * when no custom configuration is provided.
     */
    public function test_get_default_config(): void
    {
        $config = new HtmlRendererConfig();

        $this->assertEquals('M d Y H:i', $config->get('date_format'));
        $this->assertEquals([], $config->get('ignore_filenames'));
        $this->assertEquals([], $config->get('ignore_extensions'));
    }

    /**
     * Tests that no exceptions are thrown when valid configurations are provided.
     *
     * This test ensures that the `StorageNavigatorConfig` constructor accepts valid configurations
     * without raising any exceptions.
     */
    public function test_valid_configurations_do_not_throw_exceptions(): void
    {
        $config = new HtmlRendererConfig([
            'date_format' => 'Y-m-d',
        ]);

        $this->assertEquals('Y-m-d', $config->get('date_format'));
    }
}
