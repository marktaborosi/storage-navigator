<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Security;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Options;
use Marktaborosi\StorageNavigator\Path\Path;
use Psr\Http\Message\ServerRequestInterface;

/**
 * The single place that decides what a client may see and do.
 *
 *  - Visibility: hidden entries (unless Options::$showHidden) and entries rejected by
 *    Options::$filter are invisible. An invisible directory hides everything below it.
 *  - Permission: the operation's feature must be enabled, and Options::$authorize
 *    (when set) must return true.
 */
final readonly class AccessPolicy
{
    public function __construct(private Options $options)
    {
    }

    /**
     * Visibility of one entry, ignoring its ancestors. Use for entries of a listing whose
     * directory has already been checked.
     */
    public function isVisible(Entry $entry): bool
    {
        if (!$this->options->showHidden && $entry->isHidden()) {
            return false;
        }

        return $this->options->filter?->matches($entry) ?? true;
    }

    /**
     * Visibility of an entry and of every directory leading to it.
     */
    public function isReachable(Entry $entry): bool
    {
        foreach ($entry->path->ancestors() as $ancestor) {
            if (!$ancestor->isRoot() && !$this->isVisible(Entry::directory($ancestor))) {
                return false;
            }
        }

        return $entry->path->isRoot() || $this->isVisible($entry);
    }

    public function isAllowed(Operation $operation, Path $path, ServerRequestInterface $request): bool
    {
        $features = $this->options->features;
        $enabled = match ($operation) {
            Operation::List => true,
            Operation::Download => $features->download,
            Operation::Preview => $features->preview,
            Operation::ZipDownload => $features->zipDownload,
            Operation::Upload => $features->upload,
            Operation::CreateDirectory => $features->createDirectory,
            Operation::Rename => $features->rename,
            Operation::Delete => $features->delete,
        };
        if (!$enabled) {
            return false;
        }
        if ($operation === Operation::List && !$features->navigation && !$path->isRoot()) {
            return false;
        }

        return $this->options->authorize === null || ($this->options->authorize)($operation, $path, $request) === true;
    }
}
