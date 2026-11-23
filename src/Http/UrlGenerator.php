<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Http;

use Marktaborosi\StorageNavigator\Path\Path;

/**
 * Builds the navigator's URLs. They are relative query strings ("?path=docs"), so the
 * navigator works under any route without knowing its own URL.
 */
final class UrlGenerator
{
    /** @var array<string, string> */
    private array $assetVersions = [];

    public function __construct(
        private readonly string $assetsDirectory,
        private readonly ?string $assetsUrl = null,
    ) {
    }

    /**
     * @param array<string, string> $extra additional query parameters (sort, order, q)
     */
    public function list(Path $directory, array $extra = []): string
    {
        return $this->build(array_filter(['path' => $directory->toString()] + $extra, static fn (string $v): bool => $v !== ''));
    }

    public function action(Action $action, Path $path): string
    {
        return $this->build(array_filter(['action' => $action->value, 'path' => $path->toString()], static fn (string $v): bool => $v !== ''));
    }

    /**
     * URL of a built-in asset or, for a theme stylesheet given as URL, the URL itself.
     */
    public function asset(string $name): string
    {
        if (preg_match('#^(?:[a-z][a-z0-9+.-]*:)?//|^/#i', $name) === 1) {
            return $name;
        }
        if ($this->assetsUrl !== null) {
            return rtrim($this->assetsUrl, '/') . '/' . rawurlencode($name);
        }

        return $this->build(['action' => Action::Asset->value, 'name' => $name, 'v' => $this->assetVersion($name)]);
    }

    public function assetVersion(string $name): string
    {
        if (!isset($this->assetVersions[$name])) {
            $file = $this->assetsDirectory . '/' . basename($name);
            $this->assetVersions[$name] = is_file($file) ? substr((string) hash_file('xxh128', $file), 0, 12) : '0';
        }

        return $this->assetVersions[$name];
    }

    /**
     * @param array<string, string> $query
     */
    private function build(array $query): string
    {
        return $query === [] ? '?' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
