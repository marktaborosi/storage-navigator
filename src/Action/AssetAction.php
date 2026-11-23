<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Action;

use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET ?action=asset&name=modern.css&v=<hash>
 * Serves the built-in CSS and JavaScript. Versioned URLs are cached for a year.
 *
 * @internal
 */
final class AssetAction
{
    private const array TYPES = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
    ];

    public function __invoke(ServerRequestInterface $request, ActionContext $context): ResponseInterface
    {
        $name = $context->parameter($request, 'name') ?? '';
        if (preg_match('/^[a-z0-9][a-z0-9.-]*\.(css|js)$/', $name, $match) !== 1) {
            throw new NotFoundException('Asset not found.');
        }
        $file = $context->assetsDirectory . '/' . $name;
        if (!is_file($file)) {
            throw new NotFoundException('Asset not found.');
        }

        $version = $context->urls->assetVersion($name);
        $etag = '"' . $version . '"';
        $cache = ($context->parameter($request, 'v') ?? '') === $version
            ? 'public, max-age=31536000, immutable'
            : 'public, max-age=300';

        if (trim($request->getHeaderLine('If-None-Match')) === $etag) {
            return $context->responder->empty(304)->withHeader('ETag', $etag)->withHeader('Cache-Control', $cache);
        }

        return $context->responder->raw((string) file_get_contents($file), self::TYPES[$match[1]])
            ->withHeader('ETag', $etag)
            ->withHeader('Cache-Control', $cache)
            ->withHeader('X-Content-Type-Options', 'nosniff');
    }
}
