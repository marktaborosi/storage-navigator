<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Action;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Marktaborosi\StorageNavigator\Exception\PayloadTooLargeException;
use Marktaborosi\StorageNavigator\Http\CallbackStream;
use Marktaborosi\StorageNavigator\Http\ContentDisposition;
use Marktaborosi\StorageNavigator\Security\Operation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ZipStream\ZipStream;

/**
 * GET ?action=zip&path=dir
 * Streams a folder as a ZIP file without temporary files. Invisible entries are left
 * out; Limits::$maxZipBytes and $maxZipEntries are checked before the first byte is sent.
 *
 * @internal
 */
final class ZipAction
{
    public function __invoke(ServerRequestInterface $request, ActionContext $context): ResponseInterface
    {
        $directory = $context->authorize($request, Operation::ZipDownload);
        if (!$directory->isDirectory()) {
            throw new NotFoundException($context->translator->trans('error.not_found'));
        }

        $limits = $context->options->limits;
        /** @var list<Entry> $entries */
        $entries = [];
        $bytes = 0;
        foreach ($context->storage->listRecursive($directory->path) as $entry) {
            if (!$context->policy->isReachable($entry)) {
                continue;
            }
            $entries[] = $entry;
            $bytes += $entry->size ?? 0;
            if (count($entries) > $limits->maxZipEntries || $bytes > $limits->maxZipBytes) {
                throw new PayloadTooLargeException($context->translator->trans('error.zip_too_large'));
            }
        }
        usort($entries, static fn (Entry $a, Entry $b): int => strcmp($a->path->toString(), $b->path->toString()));

        $storage = $context->storage;
        $base = $directory->path;
        $body = new CallbackStream(static function ($output) use ($entries, $storage, $base): void {
            $zip = new ZipStream(outputStream: $output, sendHttpHeaders: false, defaultEnableZeroHeader: true);
            foreach ($entries as $entry) {
                $name = $entry->path->relativeTo($base);
                $modified = $entry->lastModified === null ? null : new \DateTimeImmutable('@' . $entry->lastModified);
                if ($entry->isDirectory()) {
                    $zip->addDirectory(fileName: $name, lastModificationDateTime: $modified);
                    continue;
                }
                $stream = $storage->readStream($entry->path);
                try {
                    $zip->addFileFromStream(fileName: $name, stream: $stream, lastModificationDateTime: $modified);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            }
            $zip->finish();
        });

        $name = ($base->isRoot() ? $context->options->title : $base->name()) . '.zip';

        return $context->responder->stream($body, 'application/zip', ContentDisposition::attachment($name));
    }
}
