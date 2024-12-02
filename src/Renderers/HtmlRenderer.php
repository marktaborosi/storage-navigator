<?php

namespace Marktaborosi\StorageBrowser\Renderers;

use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserNavigationHandlerInterface;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserRendererInterface;
use Marktaborosi\StorageBrowser\Renderers\Entities\RenderData;
use Marktaborosi\StorageBrowser\Renderers\Navigators\HttpNavigationHandler;
use Marktaborosi\StorageBrowser\Traits\PathHelperTrait;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

/**
 * Class HtmlRenderer
 *
 * Renders the file browser output as HTML using the Twig templating engine.
 * This renderer uses a configurable theme for styling the output and can optionally
 * disable navigation and file download features.
 *
 * @package Marktaborosi\StorageBrowser\Renderers
 * @pattern Strategy
 */
class HtmlRenderer implements StorageBrowserRendererInterface
{
    use PathHelperTrait;

    /**
     * @var Environment The Twig environment used for rendering templates.
     */
    private Environment $twig;

    /**
     * @var string The name or path of the theme used for styling.
     */
    private string $theme;

    /**
     * @var string The full path to the theme's CSS file.
     */
    private string $themeHtmlPath;

    /**
     * @var bool Flag to disable navigation functionality.
     */
    private bool $disableNavigation;

    /**
     * @var bool Flag to disable file download functionality.
     */
    private bool $disableFileDownload;

    /**
     * HtmlRenderer constructor.
     *
     * Initializes the Twig environment and sets the theme for rendering.
     *
     * @param string $theme The theme name or full theme path. If a full path is provided, it will be used directly. Otherwise, the theme name is resolved to a CSS file in the 'assets' directory.
     * @param bool $disableNavigation Flag to disable navigation functionality.
     * @param bool $disableFileDownload Flag to disable file download functionality.
     */
    public function __construct(
        string $theme,
        bool   $disableNavigation = false,
        bool   $disableFileDownload = false
    )
    {
        $loader = new FilesystemLoader(realpath(__DIR__ . '/../../templates'));
        $this->twig = new Environment($loader);

        if (file_exists($theme)) {
            $this->themeHtmlPath = $theme;
        } else {
            $this->themeHtmlPath = '/../public/css/' . $theme . '.min.css';
        }

        $this->disableNavigation = $disableNavigation;
        $this->disableFileDownload = $disableFileDownload;
    }

    /**
     * Renders the file browser output as HTML using Twig.
     *
     * @param RenderData $data Data needed for rendering, including location, files, and configuration.
     * @return void
     * @throws LoaderError If the template cannot be found.
     * @throws RuntimeError If an error occurs during template rendering.
     * @throws SyntaxError If there's a syntax error in the template.
     */
    public function render(RenderData $data): void
    {
        // Theme not found
        if (!$this->themeExists()) {
            http_response_code(404);
            echo $this->twig->render("error/theme_not_found.html.twig", ["theme_path" => $this->themeHtmlPath]);
        } else {
            // Prepare dataset
            $dateFormat = $data->getConfiguration()->get("date_format");
            $data = [
                'current_path' => $data->getCurrentPath(),
                'root_path' => $data->getRootPath(),
                'back_path' => $this->getBackPath($data->getCurrentPath(), $data->getRootPath()),
                'theme_path' => $this->themeHtmlPath,
                'files' => $data->getStructure()->toArray(),
                'disable_navigation' => $this->disableNavigation,
                'disable_file_download' => $this->disableFileDownload
            ];

            // Add date formatting function
            $this->twig->addFunction(new TwigFunction("format", function (int $time) use ($dateFormat) {
                return date($dateFormat, $time);
            }));

            // Render layout
            echo $this->twig->render('layout.html.twig', $data);
        }
    }

    /**
     * Returns the navigation handler responsible for handling navigation-related operations.
     *
     * @return StorageBrowserNavigationHandlerInterface The navigation handler instance for managing HTTP-based navigation.
     */
    public function navigationHandler(): StorageBrowserNavigationHandlerInterface
    {
        return new HttpNavigationHandler();
    }

    /**
     * Retrieves the parent directory path for navigating back.
     *
     * @param string $currentPath The current directory path.
     * @param string $rootPath The root directory path.
     * @return string|null The parent directory path or null if at the root directory.
     */
    private function getBackPath(string $currentPath, string $rootPath): ?string
    {
        if ($this->getNormalizedDirname($currentPath) === $this->getNormalizedDirname($rootPath)) {
            return null;
        }
        $dir = dirname($currentPath);
        if ($dir === '.' || $dir === './' || $dir === "../" || $dir === "\\") {
            $dir = "";
        }
        return $dir;
    }

    /**
     * Checks if the theme file exists.
     *
     * @return bool True if the theme file exists, false otherwise.
     */
    private function themeExists(): bool
    {
        if (str_contains($this->themeHtmlPath, "../public/css")) {
            $fullPath = __DIR__ . str_replace("/../", "/../../", $this->themeHtmlPath);
            if (!file_exists($fullPath)) {
                return false;
            }
            return true;
        }
        return true;
    }

    /**
     * Retrieves a list of available themes by listing all .css files and removing the .min.css suffix.
     *
     * @return array List of theme names without the .min.css suffix.
     */
    public final static function getThemeList(): array
    {
        // Get all .css files in the directory
        $files = glob(realpath(__DIR__ . "/../../public/css/") . DIRECTORY_SEPARATOR . "*.css");

        $cssFiles = [];

        // Iterate through the files
        foreach ($files as $file) {
            // Get the base name of the file (without path)
            $fileName = basename($file);

            // Remove .min.css if it exists, otherwise, keep the original name
            $fileNameWithoutMin = preg_replace('/\.min\.css$/', '', $fileName);

            // Add the processed file name to the array
            $cssFiles[] = $fileNameWithoutMin;
        }

        return $cssFiles;
    }
}
