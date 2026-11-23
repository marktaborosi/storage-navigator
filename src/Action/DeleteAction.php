<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Action;

use Marktaborosi\StorageNavigator\Exception\BadRequestException;
use Marktaborosi\StorageNavigator\Exception\ConflictException;
use Marktaborosi\StorageNavigator\Security\Operation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST action=delete, path=entry
 *
 * A folder is only deleted when everything in it is visible to the client: hidden
 * files and filtered-out entries are never removed as a side effect.
 *
 * @internal
 */
final class DeleteAction
{
    public function __invoke(ServerRequestInterface $request, ActionContext $context): ResponseInterface
    {
        $entry = $context->authorize($request, Operation::Delete);
        $t = $context->translator;
        if ($entry->path->isRoot()) {
            throw new BadRequestException($t->trans('error.root'));
        }

        if ($entry->isDirectory()) {
            foreach ($context->storage->listRecursive($entry->path) as $child) {
                if (!$context->policy->isReachable($child)) {
                    throw new ConflictException($t->trans('error.contains_protected', ['name' => $entry->name()]));
                }
            }
        }

        $context->writableStorage()->delete($entry->path);

        return $context->writeSuccess($request, $entry->path->parent(), $t->trans('toast.deleted', ['name' => $entry->name()]));
    }
}
