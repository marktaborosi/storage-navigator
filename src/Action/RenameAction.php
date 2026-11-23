<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Action;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Exception\BadRequestException;
use Marktaborosi\StorageNavigator\Exception\ConflictException;
use Marktaborosi\StorageNavigator\Exception\InvalidPathException;
use Marktaborosi\StorageNavigator\Path\Path;
use Marktaborosi\StorageNavigator\Security\Operation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST action=rename, path=entry, name=new-name
 * Renames within the same folder; moving between folders is not offered.
 *
 * @internal
 */
final class RenameAction
{
    public function __invoke(ServerRequestInterface $request, ActionContext $context): ResponseInterface
    {
        $entry = $context->authorize($request, Operation::Rename);
        $t = $context->translator;
        if ($entry->path->isRoot()) {
            throw new BadRequestException($t->trans('error.root'));
        }

        $name = trim($context->parameter($request, 'name') ?? '');
        try {
            Path::assertValidName($name);
            $target = $entry->path->parent()->child($name);
        } catch (InvalidPathException) {
            throw new BadRequestException($t->trans('error.invalid_name', ['name' => $name]));
        }
        if ($target->equals($entry->path)) {
            return $context->writeSuccess($request, $entry->path->parent(), $t->trans('toast.renamed', ['name' => $name]));
        }
        if ($entry->isFile() && !$context->options->isFileNameAllowed($name)) {
            throw new BadRequestException($t->trans('error.type_not_allowed', ['name' => $name]));
        }
        $renamed = new Entry($target, $entry->type, $entry->size, $entry->lastModified, $entry->mimeType);
        if (!$context->policy->isVisible($renamed)) {
            throw new BadRequestException($t->trans('error.name_not_allowed', ['name' => $name]));
        }
        $existing = $context->storage->stat($target);
        // A case-only rename ("a.txt" -> "A.txt") finds the entry itself on case-insensitive disks.
        if ($existing !== null && mb_strtolower($target->toString()) !== mb_strtolower($entry->path->toString())) {
            throw new ConflictException($t->trans('error.exists', ['name' => $name]));
        }

        $context->writableStorage()->move($entry->path, $target);

        return $context->writeSuccess($request, $entry->path->parent(), $t->trans('toast.renamed', ['name' => $name]));
    }
}
