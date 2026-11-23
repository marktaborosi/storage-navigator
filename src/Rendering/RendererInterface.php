<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Rendering;

/**
 * Turns view data (see ViewFactory) into HTML.
 * Implement it to render the navigator with a different template engine.
 */
interface RendererInterface
{
    /**
     * @param array<string, mixed> $view
     */
    public function renderListing(array $view): string;

    /**
     * @param array<string, mixed> $view
     */
    public function renderError(array $view): string;
}
