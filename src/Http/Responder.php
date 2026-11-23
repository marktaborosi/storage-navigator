<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Creates the navigator's PSR-7 responses.
 */
final readonly class Responder
{
    public function __construct(
        private ResponseFactoryInterface $responses,
        private StreamFactoryInterface $streams,
        private bool $securityHeaders,
        private ?string $assetsOrigin = null,
    ) {
    }

    public function html(string $html, int $status = 200): ResponseInterface
    {
        $response = $this->responses->createResponse($status)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store')
            ->withBody($this->streams->createStream($html));

        if (!$this->securityHeaders) {
            return $response;
        }
        $external = $this->assetsOrigin === null ? '' : ' ' . $this->assetsOrigin;

        return $response
            ->withHeader('Content-Security-Policy', implode('; ', [
                "default-src 'none'",
                "script-src 'self'" . $external,
                "style-src 'self'" . $external,
                "font-src 'self'" . $external,
                "img-src 'self' data: blob:",
                "media-src 'self'",
                "frame-src 'self'",
                "connect-src 'self'",
                "form-action 'self'",
                "base-uri 'none'",
                "frame-ancestors 'self'",
            ]))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('Referrer-Policy', 'same-origin');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->responses->createResponse($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withBody($this->streams->createStream(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));
    }

    public function redirect(string $location): ResponseInterface
    {
        return $this->responses->createResponse(303)
            ->withHeader('Location', $location)
            ->withHeader('Cache-Control', 'no-store');
    }

    /**
     * Streams a file, honouring a single Range request when the size is known.
     *
     * @param resource                     $resource
     * @param array<string, string>        $headers  extra headers
     */
    public function file(
        ServerRequestInterface $request,
        $resource,
        string $fileName,
        string $contentType,
        ?int $size,
        bool $inline,
        ?int $lastModified = null,
        array $headers = [],
    ): ResponseInterface {
        $response = $this->responses->createResponse(200)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Disposition', $inline ? ContentDisposition::inline($fileName) : ContentDisposition::attachment($fileName))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'private, no-cache');
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        if ($lastModified !== null) {
            $response = $response->withHeader('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
        }
        if ($size === null) {
            return $response->withHeader('Accept-Ranges', 'none')->withBody(new ResourceStream($resource));
        }

        $response = $response->withHeader('Accept-Ranges', 'bytes');
        $range = $request->getMethod() === 'GET' ? ByteRange::parse($request->getHeaderLine('Range'), $size) : null;
        if ($range !== null) {
            return $response->withStatus(206)
                ->withHeader('Content-Range', $range->contentRange($size))
                ->withHeader('Content-Length', (string) $range->length())
                ->withBody(new ResourceStream($resource, $range->start, $range->length()));
        }

        return $response
            ->withHeader('Content-Length', (string) $size)
            ->withBody(new ResourceStream($resource, 0, $size));
    }

    public function stream(CallbackStream $body, string $contentType, string $disposition): ResponseInterface
    {
        return $this->responses->createResponse(200)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Disposition', $disposition)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'private, no-store')
            ->withBody($body);
    }

    public function raw(string $body, string $contentType, int $status = 200): ResponseInterface
    {
        return $this->responses->createResponse($status)
            ->withHeader('Content-Type', $contentType)
            ->withBody($this->streams->createStream($body));
    }

    public function empty(int $status): ResponseInterface
    {
        return $this->responses->createResponse($status);
    }
}
