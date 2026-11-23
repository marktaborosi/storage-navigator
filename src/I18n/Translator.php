<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\I18n;

/**
 * UI strings. Built-in locales live in resources/translations/<locale>.php;
 * unknown locales fall back to English, and individual keys can be overridden.
 */
final class Translator
{
    /** @var array<string, string> */
    private array $messages;

    /**
     * @param array<string, string> $overrides
     */
    public function __construct(private readonly string $locale = 'en', array $overrides = [])
    {
        $fallback = self::load('en');
        $this->messages = array_merge($fallback, $locale === 'en' ? [] : self::load($locale), $overrides);
    }

    /**
     * @return list<string>
     */
    public static function availableLocales(): array
    {
        $files = glob(self::directory() . '/*.php') ?: [];

        return array_map(static fn (string $file): string => basename($file, '.php'), $files);
    }

    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * @param array<string, string|int|float> $parameters replaced in "{name}" placeholders
     */
    public function trans(string $key, array $parameters = []): string
    {
        $message = $this->messages[$key] ?? $key;
        if ($parameters === []) {
            return $message;
        }
        $replacements = [];
        foreach ($parameters as $name => $value) {
            $replacements['{' . $name . '}'] = (string) $value;
        }

        return strtr($message, $replacements);
    }

    /**
     * Every message whose key starts with one of the prefixes (for the client-side script).
     *
     * @param list<string> $prefixes
     *
     * @return array<string, string>
     */
    public function subset(array $prefixes): array
    {
        return array_filter(
            $this->messages,
            static function (string $key) use ($prefixes): bool {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($key, $prefix)) {
                        return true;
                    }
                }

                return false;
            },
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @return array<string, string>
     */
    private static function load(string $locale): array
    {
        if (preg_match('/^[a-z]{2,3}(?:[_-][A-Za-z]{2,4})?$/', $locale) !== 1) {
            return [];
        }
        $file = self::directory() . '/' . $locale . '.php';
        if (!is_file($file)) {
            return [];
        }
        /** @var array<string, string> $messages */
        $messages = require $file;

        return $messages;
    }

    private static function directory(): string
    {
        return dirname(__DIR__, 2) . '/resources/translations';
    }
}
