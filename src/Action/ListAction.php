<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Action;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Entry\Listing;
use Marktaborosi\StorageNavigator\Entry\SortDirection;
use Marktaborosi\StorageNavigator\Entry\SortField;
use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Marktaborosi\StorageNavigator\Filter\Filter;
use Marktaborosi\StorageNavigator\Security\Operation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET ?path=dir[&sort=name|size|modified|type][&order=asc|desc][&q=text]
 * HTML page, or JSON when the client accepts application/json.
 *
 * @internal
 */
final class ListAction
{
    public function __invoke(ServerRequestInterface $request, ActionContext $context): ResponseInterface
    {
        $entry = $context->authorize($request, Operation::List);
        if (!$entry->isDirectory()) {
            throw new NotFoundException($context->translator->trans('error.not_found'));
        }

        $sortField = SortField::tryFrom($context->parameter($request, 'sort') ?? '') ?? SortField::Name;
        $sortDirection = SortDirection::tryFrom($context->parameter($request, 'order') ?? '') ?? SortDirection::Ascending;
        $query = mb_substr(trim($context->parameter($request, 'q') ?? ''), 0, 200);

        $listing = (new Listing($entry->path, $context->storage->list($entry->path)))
            ->filter(Filter::callback($context->policy->isVisible(...)))
            ->search($query)
            ->sorted($sortField, $sortDirection);

        if ($context->wantsJson($request)) {
            return $context->responder->json([
                'path' => $entry->path->toString(),
                'entries' => array_map(static fn (Entry $child): array => [
                    'name' => $child->name(),
                    'path' => $child->path->toString(),
                    'type' => $child->type->value,
                    'size' => $child->size,
                    'lastModified' => $child->lastModified,
                    'mimeType' => $child->mimeType,
                ], $listing->entries),
            ]);
        }

        $csrf = null;
        $setCookie = null;
        if ($context->csrf !== null) {
            ['token' => $csrf, 'setCookie' => $setCookie] = $context->csrf->issue($request);
        }

        $view = $context->views->listing($request, $listing, $sortField, $sortDirection, $query, $csrf);
        $response = $context->responder->html($context->renderer->renderListing($view));

        return $setCookie === null ? $response : $response->withAddedHeader('Set-Cookie', $setCookie);
    }
}
