<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Action;

use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Marktaborosi\StorageNavigator\Exception\PayloadTooLargeException;
use Marktaborosi\StorageNavigator\Preview\PreviewType;
use Marktaborosi\StorageNavigator\Security\Operation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET ?action=download&path=file  – as an attachment
 * GET ?action=raw&path=file       – inline, for the preview (previewable types only)
 *
 * Inline responses are locked down: text is always text/plain, and everything except
 * PDF is sandboxed by CSP, so an uploaded HTML or SVG file cannot run script on this origin.
 *
 * @internal
 */
final readonly class DownloadAction
{
    public function __construct(private bool $inline)
    {
    }

    public function __invoke(ServerRequestInterface $request, ActionContext $context): ResponseInterface
    {
        $entry = $context->authorize($request, $this->inline ? Operation::Preview : Operation::Download);
        if (!$entry->isFile()) {
            throw new NotFoundException($context->translator->trans('error.not_found'));
        }

        $headers = [];
        if ($this->inline) {
            $type = $context->previews->resolve($entry);
            if ($type === null) {
                if ($context->previews->isTooLargeForText($entry)) {
                    throw new PayloadTooLargeException($context->translator->trans('preview.too_large'));
                }
                throw new NotFoundException($context->translator->trans('preview.unavailable'));
            }
            $contentType = $context->previews->contentType($entry, $type);
            $headers['Cross-Origin-Resource-Policy'] = 'same-origin';
            if ($type !== PreviewType::Pdf) {
                $headers['Content-Security-Policy'] = "default-src 'none'; img-src 'self' data:; media-src 'self'; style-src 'unsafe-inline'; sandbox";
            }
        } else {
            $contentType = $entry->mimeType ?? 'application/octet-stream';
        }

        return $context->responder->file(
            $request,
            $context->storage->readStream($entry->path),
            $entry->name(),
            $contentType,
            $entry->size,
            $this->inline,
            $entry->lastModified,
            $headers,
        );
    }
}
