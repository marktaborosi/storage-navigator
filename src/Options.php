<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator;

use Marktaborosi\StorageNavigator\Exception\ConfigurationException;
use Marktaborosi\StorageNavigator\Filter\FilterInterface;
use Marktaborosi\StorageNavigator\Path\Path;
use Marktaborosi\StorageNavigator\Rendering\Theme;
use Marktaborosi\StorageNavigator\Security\Operation;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Configuration of a StorageNavigator. Every argument is optional; use named arguments.
 *
 * <code>
 * new Options(
 *     title: 'Project files',
 *     theme: 'modern',
 *     locale: 'hu',
 *     features: new Features(upload: true, createDirectory: true),
 *     csrfSecret: $_ENV['STORAGE_NAVIGATOR_SECRET'],
 *     authorize: fn (Operation $op, Path $path, ServerRequestInterface $request): bool
 *         => !$op->isWrite() || $currentUser->isAdmin(),
 * );
 * </code>
 */
final readonly class Options
{
    /** Server-side executables and web server configuration files. */
    public const array DEFAULT_DENIED_UPLOAD_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phps', 'phar', 'inc',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'asp', 'aspx', 'ashx', 'jsp', 'jspx',
        'exe', 'bat', 'cmd', 'com', 'dll', 'so', 'htaccess', 'htpasswd', 'shtml',
    ];

    public Theme $theme;

    /** @var \Closure(Operation, Path, ServerRequestInterface): bool|null */
    public ?\Closure $authorize;

    /** @var list<string> */
    public array $deniedUploadExtensions;

    /** @var list<string>|null */
    public ?array $allowedUploadExtensions;

    /**
     * @param string|Theme                                                  $theme                   a built-in theme name (see Theme::names()) or a custom Theme
     * @param string                                                        $locale                  "en" or "hu" (or any locale you provide $translations for)
     * @param array<string, string>                                         $translations            overrides for individual UI strings, by key
     * @param FilterInterface|null                                          $filter                  entries that do not match are invisible and inaccessible
     * @param bool                                                          $showHidden              show dotfiles (".env", ".git"); off by default
     * @param string|null                                                   $csrfSecret              at least 32 characters; required for write features
     * @param callable(Operation, Path, ServerRequestInterface): bool|null $authorize               called for every operation that features allow
     * @param list<string>|null                                             $allowedUploadExtensions when set, only these extensions may be uploaded
     * @param list<string>                                                  $deniedUploadExtensions  never accepted for uploads or renames
     * @param bool                                                          $overwriteUploads        replace existing files on upload instead of rejecting
     * @param bool                                                          $securityHeaders         send CSP, X-Frame-Options and related headers with HTML pages
     * @param string|null                                                   $assetsUrl               serve CSS/JS from this URL (for example a CDN or public dir) instead of through the handler
     * @param list<string>                                                  $templatePaths           directories searched before the built-in templates, to override them
     * @param string|null                                                   $timezone                time zone for displayed dates; defaults to date_default_timezone_get()
     * @param LoggerInterface|null                                          $logger                  receives storage failures (clients only see a generic error)
     * @param string|null                                                   $cacheDirectory          Twig template cache; recommended in production
     */
    public function __construct(
        public string $title = 'Files',
        string|Theme $theme = 'modern',
        public string $locale = 'en',
        public array $translations = [],
        public string $dateFormat = 'Y-m-d H:i',
        public ?string $timezone = null,
        public Features $features = new Features(),
        public Limits $limits = new Limits(),
        public ?FilterInterface $filter = null,
        public bool $showHidden = false,
        #[\SensitiveParameter] public ?string $csrfSecret = null,
        ?callable $authorize = null,
        ?array $allowedUploadExtensions = null,
        array $deniedUploadExtensions = self::DEFAULT_DENIED_UPLOAD_EXTENSIONS,
        public bool $overwriteUploads = false,
        public bool $securityHeaders = true,
        public ?string $assetsUrl = null,
        public array $templatePaths = [],
        public ?LoggerInterface $logger = null,
        public ?string $cacheDirectory = null,
    ) {
        $this->theme = $theme instanceof Theme ? $theme : Theme::named($theme);
        $this->authorize = $authorize === null ? null : $authorize(...);
        $this->allowedUploadExtensions = $allowedUploadExtensions === null ? null : self::normalizeExtensions($allowedUploadExtensions);
        $this->deniedUploadExtensions = self::normalizeExtensions($deniedUploadExtensions);

        if ($features->hasWriteFeature() && ($csrfSecret === null || strlen($csrfSecret) < 32)) {
            throw new ConfigurationException(
                'Write features (upload, createDirectory, rename, delete) need Options::$csrfSecret, a random string of at least 32 characters. '
                . 'Generate one with bin2hex(random_bytes(32)) and keep it in your configuration.',
            );
        }
        if ($timezone !== null && !in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            throw new ConfigurationException(sprintf('Unknown time zone "%s".', $timezone));
        }
    }

    /**
     * @param array<mixed> $extensions
     *
     * @return list<string>
     */
    private static function normalizeExtensions(array $extensions): array
    {
        $normalized = [];
        foreach ($extensions as $extension) {
            if (!is_string($extension)) {
                throw new ConfigurationException('Upload extensions must be strings.');
            }
            $normalized[] = mb_strtolower(ltrim($extension, '.'));
        }

        return $normalized;
    }

    /**
     * Whether a file name may be created through upload or rename.
     *
     * Every dot-separated part is checked against the deny list, not only the last one,
     * because some web servers execute "shell.php.jpg" as PHP.
     */
    public function isFileNameAllowed(string $name): bool
    {
        $parts = explode('.', mb_strtolower($name));
        array_shift($parts);
        foreach ($parts as $part) {
            if (in_array($part, $this->deniedUploadExtensions, true)) {
                return false;
            }
        }
        if ($this->allowedUploadExtensions === null) {
            return true;
        }
        $extension = $parts === [] ? '' : $parts[array_key_last($parts)];

        return in_array($extension, $this->allowedUploadExtensions, true);
    }
}
