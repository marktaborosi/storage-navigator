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
 * This renderer allows customization of themes and supports enabling or disabling
 * navigation and file download features.
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
    private string $themePath;

    /**
     * @var string The contents of the theme's CSS file.
     */
    private string $cssContents;

    /**
     * @var bool Whether navigation functionality is disabled.
     */
    private bool $disableNavigation;

    /**
     * @var bool Whether file download functionality is disabled.
     */
    private bool $disableFileDownload;

    /**
     * Constructor for the HtmlRenderer.
     *
     * Sets up the Twig environment and initializes the theme.
     *
     * @param string $themePath The theme name or path.
     * @param bool $disableNavigation Whether navigation is disabled.
     * @param bool $disableFileDownload Whether file downloads are disabled.
     * @throws LoaderError If there is an issue loading templates.
     * @throws RuntimeError If a runtime error occurs.
     * @throws SyntaxError If there is a syntax error in a template.
     */
    public function __construct(
        string $themePath,
        bool $disableNavigation = false,
        bool $disableFileDownload = false
    ) {
        $loader = new FilesystemLoader(realpath(__DIR__ . '/../../templates'));
        $this->twig = new Environment($loader);

        $fullPath = __DIR__ . '/../../public/css/' . $themePath . '.min.css';
        if (file_exists($themePath)) {
            $this->cssContents = file_get_contents($themePath);
            $this->themePath = $themePath;
        } elseif (file_exists($fullPath)) {
            $this->cssContents = file_get_contents($fullPath);
            $this->themePath = $fullPath;
        } else {
            http_response_code(404);
            echo $this->twig->render("error/theme_not_found.html.twig", ["theme_path" => $themePath]);
            exit;
        }

        $this->disableNavigation = $disableNavigation;
        $this->disableFileDownload = $disableFileDownload;
    }

    /**
     * Renders the file browser as HTML.
     *
     * @param RenderData $data Data to be rendered, including file structure and paths.
     * @return void
     * @throws LoaderError If there is an issue loading templates.
     * @throws RuntimeError If a runtime error occurs.
     * @throws SyntaxError If there is a syntax error in a template.
     */
    public function render(RenderData $data): void
    {
        $dateFormat = $data->getConfiguration()->get("date_format");
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
     * Retrieves the navigation handler for managing navigation operations.
     *
     * @return StorageBrowserNavigationHandlerInterface The navigation handler.
     */
    public function navigationHandler(): StorageBrowserNavigationHandlerInterface
    {
        return new HttpNavigationHandler();
    }

    /**
     * Gets the parent directory for back navigation.
     *
     * @param string $currentPath The current path.
     * @param string $rootPath The root path.
     * @return string|null The parent directory path or null if at the root.
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
     * Gets a list of available themes from the CSS directory.
     *
     * @return array The list of theme names without the .min.css suffix.
     */
    public final static function getThemeList(): array
    {
        $files = glob(realpath(__DIR__ . "/../../public/css/") . DIRECTORY_SEPARATOR . "*.css");
        return array_map(function ($file) {
            return preg_replace('/\.min\.css$/', '', basename($file));
        }, $files);
    }
}
