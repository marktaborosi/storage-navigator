<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Rendering;

use Marktaborosi\StorageNavigator\Exception\ConfigurationException;

/**
 * A visual theme: a layout (a set of templates) plus stylesheets.
 *
 * Built-in layouts: "modern" (cards, grid and list views) and "console"
 * (full-screen, keyboard-driven panel with function keys). Stylesheets are either
 * built-in asset names ("modern.css") or URLs ("/css/my-theme.css").
 */
final readonly class Theme
{
    public const string LAYOUT_MODERN = 'modern';
    public const string LAYOUT_CONSOLE = 'console';

    private const array BUILT_IN = [
        'modern' => [self::LAYOUT_MODERN, ['modern.css'], 'auto'],
        'modern-light' => [self::LAYOUT_MODERN, ['modern.css'], 'light'],
        'modern-dark' => [self::LAYOUT_MODERN, ['modern.css'], 'dark'],
        'norton-commander' => [self::LAYOUT_CONSOLE, ['console.css', 'console-norton-commander.css'], 'dark'],
        'midnight-commander' => [self::LAYOUT_CONSOLE, ['console.css', 'console-midnight-commander.css'], 'dark'],
        'hacker' => [self::LAYOUT_CONSOLE, ['console.css', 'console-hacker.css'], 'dark'],
        'terminal' => [self::LAYOUT_CONSOLE, ['console.css', 'console-terminal.css'], 'dark'],
    ];

    /** Theme names of version 2, mapped to their closest replacement. */
    private const array LEGACY = [
        'basic-light' => 'modern-light',
        'basic-dark' => 'modern-dark',
        'basic-mac' => 'modern-light',
        'bootstrap-mason-light' => 'modern-light',
        'basic-norton' => 'norton-commander',
        'console-norton-commander' => 'norton-commander',
        'console-midnight-commander' => 'midnight-commander',
        'console-hacker' => 'hacker',
        'basic-terminal' => 'terminal',
    ];

    /**
     * @param list<string>             $stylesheets built-in asset names or URLs, loaded in order
     * @param 'auto'|'light'|'dark'    $colorScheme
     */
    public function __construct(
        public string $name,
        public string $layout,
        public array $stylesheets,
        public string $colorScheme = 'auto',
    ) {
        if (!in_array($layout, [self::LAYOUT_MODERN, self::LAYOUT_CONSOLE], true)) {
            throw new ConfigurationException(sprintf('Unknown layout "%s". Use "modern" or "console".', $layout));
        }
        if (!in_array($colorScheme, ['auto', 'light', 'dark'], true)) {
            throw new ConfigurationException('The color scheme must be "auto", "light" or "dark".');
        }
    }

    public static function named(string $name): self
    {
        if (isset(self::LEGACY[$name])) {
            @trigger_error(sprintf('The theme name "%s" is deprecated since 3.0, use "%s".', $name, self::LEGACY[$name]), E_USER_DEPRECATED);
            $name = self::LEGACY[$name];
        }
        if (!isset(self::BUILT_IN[$name])) {
            throw new ConfigurationException(sprintf('Unknown theme "%s". Available themes: %s.', $name, implode(', ', self::names())));
        }
        [$layout, $stylesheets, $scheme] = self::BUILT_IN[$name];

        return new self($name, $layout, $stylesheets, $scheme);
    }

    /**
     * A built-in layout with your own stylesheets on top (or instead).
     *
     * @param list<string>          $stylesheets
     * @param 'auto'|'light'|'dark' $colorScheme
     */
    public static function custom(string $name, string $layout, array $stylesheets, string $colorScheme = 'auto'): self
    {
        return new self($name, $layout, $stylesheets, $colorScheme);
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::BUILT_IN);
    }
}
