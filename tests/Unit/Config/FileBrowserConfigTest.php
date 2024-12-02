<?php

namespace Marktaborosi\Tests\Unit\Config;

use InvalidArgumentException;
use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use PHPUnit\Framework\TestCase;

/**
 * Class FileBrowserConfigTest
 *
 * This class tests the functionality of the `FileBrowserConfig` class,
 * ensuring proper behavior when configurations are provided, validated,
 * and retrieved.
 *
 * @package Marktaborosi\Tests\Unit\Config
 */
class FileBrowserConfigTest extends TestCase
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
            'ignore_filenames' => ['testfile.txt'],
            'ignore_extensions' => ['log']
        ];

        $config = new FileBrowserConfig($customConfig);

        $this->assertEquals('Y-m-d', $config->get('date_format'));
        $this->assertEquals(['testfile.txt'], $config->get('ignore_filenames'));
        $this->assertEquals(['log'], $config->get('ignore_extensions'));
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

        new FileBrowserConfig(['invalid_key' => 'value']);
    }

    /**
     * Tests that the `get()` method returns a default value if the requested key does not exist.
     *
     * This test ensures that the `get()` method correctly falls back to the default value
     * when the requested configuration key is missing.
     */
    public function test_get_returns_default_value_if_key_does_not_exist(): void
    {
        $config = new FileBrowserConfig();

        $this->assertEquals('default_value', $config->get('non_existent_key', 'default_value'));
    }

    /**
     * Tests that an exception is thrown when `date_format` is not a string.
     *
     * This test ensures that the `FileBrowserConfig` constructor validates the `date_format`
     * field and throws an exception if it is not a string.
     */
    public function test_constructor_throws_exception_for_invalid_date_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration - date_format must be a string');

        new FileBrowserConfig(['date_format' => 12345]);
    }

    /**
     * Tests that an exception is thrown when `ignore_filenames` is not an array.
     *
     * This test ensures that the `FileBrowserConfig` constructor validates the `ignore_filenames`
     * field and throws an exception if it is not an array.
     */
    public function test_constructor_throws_exception_for_invalid_ignore_filenames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration - ignore_filenames must be an array');

        new FileBrowserConfig(['ignore_filenames' => 'not_an_array']);
    }

    /**
     * Tests that an exception is thrown when `ignore_extensions` is not an array.
     *
     * This test ensures that the `FileBrowserConfig` constructor validates the `ignore_extensions`
     * field and throws an exception if it is not an array.
     */
    public function test_constructor_throws_exception_for_invalid_ignore_extensions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration - ignore_extensions must be an array');

        new FileBrowserConfig(['ignore_extensions' => 'not_an_array']);
    }

    /**
     * Tests that an exception is thrown for invalid filenames in `ignore_filenames`.
     *
     * This test ensures that the `FileBrowserConfig` constructor validates each filename
     * in the `ignore_filenames` list and throws an exception if any filename contains invalid characters.
     */
    public function test_constructor_throws_exception_for_invalid_filenames(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration - [invalid|file.txt] containing invalid characters in ignore_filenames');

        new FileBrowserConfig(['ignore_filenames' => ['valid_file.txt', 'invalid|file.txt']]);
    }

    /**
     * Tests that an exception is thrown for invalid extensions in `ignore_extensions`.
     *
     * This test ensures that the `FileBrowserConfig` constructor validates each extension
     * in the `ignore_extensions` list and throws an exception if any extension contains invalid characters.
     */
    public function test_constructor_throws_exception_for_invalid_extensions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid configuration - [inv@lid] containing invalid characters in ignore_extensions');

        new FileBrowserConfig(['ignore_extensions' => ['validExtension', 'inv@lid']]);
    }

    /**
     * Tests that `getDefaultConfig()` returns the correct default configuration values.
     *
     * This test verifies that the default configuration values are correctly returned
     * when no custom configuration is provided.
     */
    public function test_get_default_config(): void
    {
        $config = new FileBrowserConfig();

        $this->assertEquals('M d Y H:i', $config->get('date_format'));
        $this->assertEquals([], $config->get('ignore_filenames'));
        $this->assertEquals([], $config->get('ignore_extensions'));
    }

    /**
     * Tests that no exceptions are thrown when valid configurations are provided.
     *
     * This test ensures that the `FileBrowserConfig` constructor accepts valid configurations
     * without raising any exceptions.
     */
    public function test_valid_configurations_do_not_throw_exceptions(): void
    {
        $config = new FileBrowserConfig([
            'date_format' => 'Y-m-d',
            'ignore_filenames' => ['valid_file.txt'],
            'ignore_extensions' => ['validExtension']
        ]);

        $this->assertEquals('Y-m-d', $config->get('date_format'));
        $this->assertEquals(['valid_file.txt'], $config->get('ignore_filenames'));
        $this->assertEquals(['validExtension'], $config->get('ignore_extensions'));
    }
}
