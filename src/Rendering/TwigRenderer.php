<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Rendering;

use Marktaborosi\StorageNavigator\I18n\Translator;
use Marktaborosi\StorageNavigator\Options;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

/**
 * The built-in Twig renderer.
 *
 * Templates are looked up in Options::$templatePaths first, then in
 * resources/templates, so any template can be overridden by placing a file with the
 * same relative name ("modern/listing.html.twig") in your own directory.
 */
final readonly class TwigRenderer implements RendererInterface
{
    private Environment $twig;

    public function __construct(
        private Options $options,
        Translator $translator,
        ?string $cacheDirectory = null,
    ) {
        $loader = new FilesystemLoader([...$options->templatePaths, dirname(__DIR__, 2) . '/resources/templates']);
        $this->twig = new Environment($loader, [
            'autoescape' => 'html',
            'strict_variables' => true,
            'cache' => $cacheDirectory ?? false,
        ]);
        $this->twig->addFunction(new TwigFunction(
            't',
            static fn (string $key, array $parameters = []): string => $translator->trans($key, $parameters),
        ));
        $this->twig->addFunction(new TwigFunction(
            'icon',
            static fn (string $name, string $class = ''): string => sprintf(
                '<svg class="icon%s" aria-hidden="true" focusable="false"><use href="#i-%s"></use></svg>',
                $class === '' ? '' : ' ' . htmlspecialchars($class, ENT_QUOTES),
                htmlspecialchars($name, ENT_QUOTES),
            ),
            ['is_safe' => ['html']],
        ));
        $this->twig->addFunction(new TwigFunction(
            'json_script',
            // Safe inside <script type="application/json">: <, >, & and quotes are escaped as \u00XX.
            static fn (mixed $data): string => json_encode(
                $data,
                JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ),
            ['is_safe' => ['html']],
        ));
    }

    public function renderListing(array $view): string
    {
        return $this->twig->render($this->options->theme->layout . '/listing.html.twig', $view);
    }

    public function renderError(array $view): string
    {
        return $this->twig->render($this->options->theme->layout . '/error.html.twig', $view);
    }
}
