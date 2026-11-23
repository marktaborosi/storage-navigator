<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Action;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Exception\BadRequestException;
use Marktaborosi\StorageNavigator\Exception\ConflictException;
use Marktaborosi\StorageNavigator\Exception\InvalidPathException;
use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Marktaborosi\StorageNavigator\Exception\PayloadTooLargeException;
use Marktaborosi\StorageNavigator\I18n\Formatter;
use Marktaborosi\StorageNavigator\Path\Path;
use Marktaborosi\StorageNavigator\Security\Operation;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * POST action=upload, path=dir, files[]=... (multipart)
 *
 * Every file is validated before anything is written, so a rejected file leaves the
 * folder unchanged.
 *
 * @internal
 */
final class UploadAction
{
    public function __invoke(ServerRequestInterface $request, ActionContext $context): ResponseInterface
    {
        $directory = $context->authorize($request, Operation::Upload);
        if (!$directory->isDirectory()) {
            throw new NotFoundException($context->translator->trans('error.not_found'));
        }

        $files = $this->flatten($request->getUploadedFiles()['files'] ?? []);
        if ($files === []) {
            throw new BadRequestException($context->translator->trans('upload.none'));
        }

        $t = $context->translator;
        $limits = $context->options->limits;
        $targets = [];
        foreach ($files as $file) {
            $error = $file->getError();
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                throw new PayloadTooLargeException($t->trans('upload.too_large', ['name' => (string) $file->getClientFilename(), 'max' => $this->max($context)]));
            }
            if ($error !== UPLOAD_ERR_OK) {
                throw new BadRequestException($t->trans('upload.failed_file', ['name' => (string) $file->getClientFilename()]));
            }
            // Browsers may send a relative path for folder uploads; only the name is used.
            $name = basename(str_replace('\\', '/', (string) $file->getClientFilename()));
            try {
                Path::assertValidName($name);
                $path = $directory->path->child($name);
            } catch (InvalidPathException) {
                throw new BadRequestException($t->trans('error.invalid_name', ['name' => $name]));
            }
            if (($file->getSize() ?? 0) > $limits->maxUploadBytes) {
                throw new PayloadTooLargeException($t->trans('upload.too_large', ['name' => $name, 'max' => $this->max($context)]));
            }
            if (!$context->options->isFileNameAllowed($name)) {
                throw new BadRequestException($t->trans('error.type_not_allowed', ['name' => $name]));
            }
            if (!$context->policy->isVisible(Entry::file($path))) {
                throw new BadRequestException($t->trans('error.name_not_allowed', ['name' => $name]));
            }
            if (isset($targets[$path->toString()])) {
                throw new ConflictException($t->trans('error.duplicate_upload', ['name' => $name]));
            }
            $existing = $context->storage->stat($path);
            if ($existing !== null && ($existing->isDirectory() || !$context->options->overwriteUploads)) {
                throw new ConflictException($t->trans('error.exists', ['name' => $name]));
            }
            $targets[$path->toString()] = [$path, $file];
        }

        $storage = $context->writableStorage();
        foreach ($targets as [$path, $file]) {
            $stream = $file->getStream()->detach();
            if (!is_resource($stream)) {
                throw new BadRequestException($t->trans('upload.failed_file', ['name' => $path->name()]));
            }
            try {
                $storage->writeStream($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }

        $message = count($targets) === 1
            ? $t->trans('toast.uploaded_one', ['name' => array_values($targets)[0][0]->name()])
            : $t->trans('toast.uploaded_other', ['count' => count($targets)]);

        return $context->writeSuccess($request, $directory->path, $message);
    }

    /**
     * @return list<UploadedFileInterface>
     */
    private function flatten(mixed $files): array
    {
        if ($files instanceof UploadedFileInterface) {
            return [$files];
        }
        if (!is_array($files)) {
            return [];
        }
        $result = [];
        foreach ($files as $file) {
            array_push($result, ...$this->flatten($file));
        }

        return $result;
    }

    private function max(ActionContext $context): string
    {
        return (new Formatter('Y', null, $context->translator->trans('number.decimal')))
            ->bytes($context->options->limits->maxUploadBytes);
    }
}
