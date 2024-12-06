<?php

namespace Marktaborosi\StorageNavigator\Renderers;

use Marktaborosi\StorageNavigator\Interfaces\StorageNavigatorActionHandlerInterface;
use Marktaborosi\StorageNavigator\Interfaces\StorageNavigatorRendererInterface;
use Marktaborosi\StorageNavigator\Renderers\Config\HtmlRendererConfig;
use Marktaborosi\StorageNavigator\Renderers\Entities\RenderData;
use Marktaborosi\StorageNavigator\Renderers\Navigators\HttpNavigationHandler;
use Marktaborosi\StorageNavigator\Traits\PathHelperTrait;
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
 * Supports customization of themes and configuration options, and allows toggling
 * navigation and file download functionality.
 *
 * @package Marktaborosi\StorageNavigator\Renderers
 * @pattern Strategy
 */
class HtmlRenderer implements StorageNavigatorRendererInterface
{
    use PathHelperTrait;

    /**
     * @var Environment The Twig environment used for rendering templates.
     */
    private Environment $twig;

    /**
     * @var string The path or name of the theme used for rendering.
     */
    private string $themePath;

    /**
     * @var string The contents of the theme's CSS file.
     */
    private string $cssContents;

    /**
     * @var bool Whether navigation is disabled.
     */
    private bool $disableNavigation;

    /**
     * @var bool Whether file download functionality is disabled.
     */
    private bool $disableFileDownload;

    /**
     * @var HtmlRendererConfig|null Configuration for the HTML renderer.
     */
    private ?HtmlRendererConfig $config;

    /**
     * Constructor for the HtmlRenderer.
     *
     * Initializes the renderer with the specified theme and configuration, and sets up
     * the Twig templating engine. If the specified theme does not exist, it displays an error page.
     *
     * @param string $themePath Path or name of the theme to be used for rendering.
     * @param HtmlRendererConfig|null $config Optional renderer configuration.
     * @param bool $disableNavigation If true, disables navigation features.
     * @param bool $disableFileDownload If true, disables file download features.
     * @throws LoaderError If there is an error loading templates.
     * @throws RuntimeError If a runtime error occurs in Twig.
     * @throws SyntaxError If a syntax error is found in a Twig template.
     */
    public function __construct(
        string $themePath,
        ?HtmlRendererConfig $config = null,
        bool $disableNavigation = false,
        bool $disableFileDownload = false
    ) {
        $loader = new FilesystemLoader(realpath(__DIR__ . '/../../templates'));
        $this->twig = new Environment($loader);

        $themeExists = true;
        $fullPath = __DIR__ . '/../../public/css/' . $themePath . '.min.css';
        if (file_exists($themePath)) {
            $this->cssContents = file_get_contents($themePath);
            $this->themePath = $themePath;
        } elseif (file_exists($fullPath)) {
            $this->cssContents = file_get_contents($fullPath);
            $this->themePath = $fullPath;
        } else {
            $themeExists = false;
            $this->setResponseCode(404);
            echo $this->twig->render("error/theme_not_found.html.twig", ["theme_path" => $themePath]);
        }

        if ($themeExists) {
            $this->config = $config;
            $this->disableNavigation = $disableNavigation;
            $this->disableFileDownload = $disableFileDownload;
        }
    }

    /**
     * Renders the file browser as an HTML page.
     *
     * @param RenderData $data The data to be rendered, including file structure and navigation paths.
     * @return void
     * @throws LoaderError If there is an error loading templates.
     * @throws RuntimeError If a runtime error occurs during rendering.
     * @throws SyntaxError If a syntax error is found in the Twig template.
     */
    public function render(RenderData $data): void
    {
        $dateFormat = $this->config->get("date_format");
        $renderData = [
            'current_path' => $data->getCurrentPath(),
            'root_path' => $data->getRootPath(),
            'back_path' => $this->getBackPath($data->getCurrentPath(), $data->getRootPath()),
            'theme_path' => $this->themePath,
            'css_contents' => $this->cssContents,
            'files' => $data->getStructure()->toArray(),
            'disable_navigation' => $this->disableNavigation,
            'disable_file_download' => $this->disableFileDownload
        ];

        if (!$this->disableNavigation) {
            $renderData['browser_navigation_js_contents'] = file_get_contents(__DIR__ . '/../../public/js/browser-navigation.min.js');
        }
        if (!$this->disableFileDownload) {
            $renderData['console_js_contents'] = file_get_contents(__DIR__ . '/../../public/js/console.min.js');
        }

        $this->twig->addFunction(new TwigFunction("format", function (int $time) use ($dateFormat) {
            return date($dateFormat, $time);
        }));

        echo $this->twig->render('layout.html.twig', $renderData);
    }

    /**
     * Retrieves the navigation handler for handling navigation operations.
     *
     * @return StorageNavigatorActionHandlerInterface The navigation handler instance.
     */
    public function navigationHandler(): StorageNavigatorActionHandlerInterface
    {
        return new HttpNavigationHandler();
    }

    /**
     * Determines the parent directory for back navigation.
     *
     * @param string $currentPath The current path in the file structure.
     * @param string $rootPath The root path of the file structure.
     * @return string|null The parent directory path, or null if at the root directory.
     */
    private function getBackPath(string $currentPath, string $rootPath): ?string
    {
        if ($this->getNormalizedDirname($currentPath) === $this->getNormalizedDirname($rootPath)) {
            return null;
        }

        $dir = dirname($currentPath);
        return ($dir === '.' || $dir === './' || $dir === "../" || $dir === "\\") ? "" : $dir;
    }

    /**
     * Retrieves a list of available themes from the CSS directory.
     *
     * @return array An array of theme names without the .min.css extension.
     */
    public final static function getThemeList(): array
    {
        $files = glob(realpath(__DIR__ . "/../../public/css/") . DIRECTORY_SEPARATOR . "*.css");
        return array_map(function ($file) {
            return preg_replace('/\.min\.css$/', '', basename($file));
        }, $files);
    }

    /**
     * Sets the HTTP response code.
     *
     * @param int $code The HTTP response code to set.
     * @return void
     */
    private function setResponseCode(int $code): void
    {
        http_response_code($code);
    }
}
