<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Action;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Exception\BadRequestException;
use Marktaborosi\StorageNavigator\Exception\ConflictException;
use Marktaborosi\StorageNavigator\Exception\InvalidPathException;
use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Marktaborosi\StorageNavigator\Path\Path;
use Marktaborosi\StorageNavigator\Security\Operation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST action=mkdir, path=parent-dir, name=new-folder
 *
 * @internal
 */
final class CreateDirectoryAction
{
    public function __invoke(ServerRequestInterface $request, ActionContext $context): ResponseInterface
    {
        $parent = $context->authorize($request, Operation::CreateDirectory);
        if (!$parent->isDirectory()) {
            throw new NotFoundException($context->translator->trans('error.not_found'));
        }

        $t = $context->translator;
        $name = trim($context->parameter($request, 'name') ?? '');
        try {
            Path::assertValidName($name);
            $path = $parent->path->child($name);
        } catch (InvalidPathException) {
            throw new BadRequestException($t->trans('error.invalid_name', ['name' => $name]));
        }
        if (!$context->policy->isVisible(Entry::directory($path))) {
            throw new BadRequestException($t->trans('error.name_not_allowed', ['name' => $name]));
        }
        if ($context->storage->stat($path) !== null) {
            throw new ConflictException($t->trans('error.exists', ['name' => $name]));
        }

        $context->writableStorage()->createDirectory($path);

        return $context->writeSuccess($request, $parent->path, $t->trans('toast.created', ['name' => $name]));
    }
}
