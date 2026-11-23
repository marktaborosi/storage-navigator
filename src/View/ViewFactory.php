<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\View;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Entry\Listing;
use Marktaborosi\StorageNavigator\Entry\SortDirection;
use Marktaborosi\StorageNavigator\Entry\SortField;
use Marktaborosi\StorageNavigator\Http\Action;
use Marktaborosi\StorageNavigator\Http\UrlGenerator;
use Marktaborosi\StorageNavigator\I18n\Formatter;
use Marktaborosi\StorageNavigator\I18n\Translator;
use Marktaborosi\StorageNavigator\Options;
use Marktaborosi\StorageNavigator\Path\Path;
use Marktaborosi\StorageNavigator\Preview\PreviewResolver;
use Marktaborosi\StorageNavigator\Security\AccessPolicy;
use Marktaborosi\StorageNavigator\Security\Operation;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Turns domain objects into the plain arrays the templates and the client script use.
 * Every permission shown in the UI is computed here with the same AccessPolicy the
 * server enforces, so the UI never offers an action the server would reject.
 */
final readonly class ViewFactory
{
    /** Message keys sent to the client-side script. */
    private const array CLIENT_MESSAGES = ['js.', 'action.', 'dialog.', 'preview.', 'upload.', 'toast.', 'search.', 'shortcuts.', 'view.', 'scheme.', 'number.'];

    public function __construct(
        private Options $options,
        private AccessPolicy $policy,
        private UrlGenerator $urls,
        private Translator $translator,
        private Formatter $formatter,
        private PreviewResolver $previews,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function listing(
        ServerRequestInterface $request,
        Listing $listing,
        SortField $sortField,
        SortDirection $sortDirection,
        string $query,
        ?string $csrfToken,
    ): array {
        $directory = $listing->directory;
        $can = fn (Operation $operation, Path $path): bool => $this->policy->isAllowed($operation, $path, $request);

        $sortParameters = static fn (SortField $field, SortDirection $direction): array => $field === SortField::Name && $direction === SortDirection::Ascending
            ? []
            : ['sort' => $field->value, 'order' => $direction->value];
        $state = $sortParameters($sortField, $sortDirection) + ($query === '' ? [] : ['q' => $query]);

        $sortUrls = [];
        foreach (SortField::cases() as $field) {
            $direction = $field === $sortField ? $sortDirection->opposite() : ($field === SortField::Name ? SortDirection::Ascending : SortDirection::Descending);
            $sortUrls[$field->value] = $this->urls->list($directory, $sortParameters($field, $direction) + ($query === '' ? [] : ['q' => $query]));
        }

        // Opening a folder keeps the sort order but not the search.
        $rows = [];
        foreach ($listing as $entry) {
            $rows[] = $this->entry($entry, $can, $sortParameters($sortField, $sortDirection));
        }

        $folders = count($listing->directories());
        $files = count($listing->files());
        $canUpload = $can(Operation::Upload, $directory);
        $canMkdir = $can(Operation::CreateDirectory, $directory);
        $canZip = $can(Operation::ZipDownload, $directory);
        $writeUrls = [
            'upload' => $this->urls->action(Action::Upload, $directory),
            'mkdir' => $this->urls->action(Action::CreateDirectory, $directory),
            'rename' => $this->urls->action(Action::Rename, Path::root()),
            'delete' => $this->urls->action(Action::Delete, Path::root()),
        ];

        $cookies = $request->getCookieParams();
        $viewMode = ($cookies['sn_view'] ?? '') === 'grid' ? 'grid' : 'list';

        return $this->page($cookies, [
            'viewMode' => $viewMode,
            'clearSearchUrl' => $this->urls->list($directory, $sortParameters($sortField, $sortDirection)),
            'directory' => [
                'path' => $directory->toString(),
                'name' => $directory->isRoot() ? $this->options->title : $directory->name(),
                'isRoot' => $directory->isRoot(),
            ],
            'breadcrumbs' => $this->breadcrumbs($directory),
            'parentUrl' => $directory->isRoot() ? null : $this->urls->list($directory->parent(), $sortParameters($sortField, $sortDirection)),
            'selfUrl' => $this->urls->list($directory, $state),
            'sort' => ['field' => $sortField->value, 'direction' => $sortDirection->value, 'urls' => $sortUrls, 'state' => $state],
            'query' => $query,
            'entries' => $rows,
            'summary' => [
                'folders' => $folders,
                'files' => $files,
                'size' => $this->formatter->bytes($listing->totalSize()),
                'text' => $this->summary($folders, $files),
            ],
            'can' => [
                'upload' => $canUpload,
                'mkdir' => $canMkdir,
                'zip' => $canZip,
                'rename' => $this->options->features->rename,
                'delete' => $this->options->features->delete,
            ],
            'urls' => $writeUrls + ['zip' => $canZip ? $this->urls->action(Action::Zip, $directory) : null],
            'csrf' => $csrfToken,
            'limits' => [
                'maxUploadBytes' => $this->options->limits->maxUploadBytes,
                'maxUpload' => $this->formatter->bytes($this->options->limits->maxUploadBytes),
            ],
            'client' => [
                'directory' => $directory->toString(),
                'csrf' => $csrfToken,
                'csrfField' => '_token',
                'urls' => $writeUrls,
                'maxUploadBytes' => $this->options->limits->maxUploadBytes,
                'layout' => $this->options->theme->layout,
                'scheme' => $this->options->theme->colorScheme,
                'messages' => $this->translator->subset(self::CLIENT_MESSAGES),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $cookies
     *
     * @return array<string, mixed>
     */
    public function error(int $status, string $message, ?string $backUrl, array $cookies = []): array
    {
        $heading = $this->translator->trans('error.' . $status);

        return $this->page($cookies, [
            'status' => $status,
            'heading' => $heading === 'error.' . $status ? $this->translator->trans('error.generic_title') : $heading,
            'message' => $message,
            'backUrl' => $backUrl,
        ]);
    }

    /**
     * Data shared by every page.
     *
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function page(array $cookies, array $data): array
    {
        $theme = $this->options->theme;
        $scheme = $theme->colorScheme;
        // The visitor's light/dark choice is kept in a cookie so the page renders without a flash.
        if ($scheme === 'auto' && in_array($cookies['sn_scheme'] ?? null, ['light', 'dark'], true)) {
            $scheme = $cookies['sn_scheme'];
        }

        return $data + [
            'title' => $this->options->title,
            'lang' => $this->translator->locale(),
            'theme' => [
                'name' => $theme->name,
                'layout' => $theme->layout,
                'scheme' => $scheme,
                'toggle' => $theme->colorScheme === 'auto',
                'stylesheets' => array_map($this->urls->asset(...), $theme->stylesheets),
            ],
            'script' => $this->urls->asset('navigator.js'),
            'rootUrl' => $this->urls->list(Path::root()),
        ];
    }

    /**
     * @param \Closure(Operation, Path): bool $can
     * @param array<string, string>           $state current sort and search parameters
     *
     * @return array<string, mixed>
     */
    private function entry(Entry $entry, \Closure $can, array $state): array
    {
        $path = $entry->path;
        $kind = FileKind::of($entry);
        $preview = $this->previews->resolve($entry);
        $canDownload = $entry->isFile() && $can(Operation::Download, $path);
        $canPreview = $preview !== null && $can(Operation::Preview, $path);
        $canZip = $entry->isDirectory() && $can(Operation::ZipDownload, $path);
        $downloadUrl = $canDownload ? $this->urls->action(Action::Download, $path) : null;
        $rawUrl = $canPreview ? $this->urls->action(Action::Raw, $path) : null;
        $openUrl = match (true) {
            $entry->isDirectory() => $can(Operation::List, $path) ? $this->urls->list($path, $state) : null,
            default => $downloadUrl ?? $rawUrl,
        };

        return [
            'name' => $entry->name(),
            'path' => $path->toString(),
            'type' => $entry->type->value,
            'isDirectory' => $entry->isDirectory(),
            'kind' => $kind->value,
            'kindLabel' => $this->translator->trans('kind.' . $kind->value),
            'extension' => $entry->extension(),
            'size' => $entry->size,
            'sizeHuman' => $this->formatter->bytes($entry->size),
            'modified' => $entry->lastModified,
            'modifiedHuman' => $this->formatter->date($entry->lastModified),
            'modifiedIso' => $this->formatter->isoDate($entry->lastModified),
            'modifiedDate' => $this->formatter->dateIn($entry->lastModified, 'Y-m-d'),
            'modifiedTime' => $this->formatter->dateIn($entry->lastModified, 'H:i'),
            'mime' => $entry->mimeType,
            'preview' => $canPreview ? $preview?->value : null,
            'url' => $openUrl,
            'downloadUrl' => $downloadUrl,
            'rawUrl' => $rawUrl,
            'zipUrl' => $canZip ? $this->urls->action(Action::Zip, $path) : null,
            'can' => [
                'rename' => $can(Operation::Rename, $path),
                'delete' => $can(Operation::Delete, $path),
            ],
        ];
    }

    private function summary(int $folders, int $files): string
    {
        $parts = [];
        if ($folders > 0) {
            $parts[] = $this->translator->trans($folders === 1 ? 'summary.folder_one' : 'summary.folder_other', ['count' => $folders]);
        }
        if ($files > 0) {
            $parts[] = $this->translator->trans($files === 1 ? 'summary.file_one' : 'summary.file_other', ['count' => $files]);
        }

        return $parts === [] ? $this->translator->trans('summary.empty') : implode(', ', $parts);
    }

    /**
     * @return list<array{name: string, url: string, current: bool}>
     */
    private function breadcrumbs(Path $directory): array
    {
        $crumbs = [];
        foreach ([...$directory->ancestors(), $directory] as $path) {
            $crumbs[] = [
                'name' => $path->isRoot() ? $this->options->title : $path->name(),
                'url' => $this->urls->list($path),
                'current' => $path->equals($directory),
            ];
        }

        return $crumbs;
    }
}
