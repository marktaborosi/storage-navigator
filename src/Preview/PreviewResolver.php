<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Preview;

use Marktaborosi\StorageNavigator\Entry\Entry;

/**
 * Decides whether and how a file can be shown in the browser, and with which
 * Content-Type its raw bytes are served for the preview.
 *
 * Text-like files are always served as text/plain, so an uploaded .html or .js file is
 * displayed as source, never executed.
 */
final readonly class PreviewResolver
{
    private const array IMAGE = [
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
        'webp' => 'image/webp', 'avif' => 'image/avif', 'bmp' => 'image/bmp', 'ico' => 'image/x-icon',
        'svg' => 'image/svg+xml',
    ];

    private const array VIDEO = [
        'mp4' => 'video/mp4', 'm4v' => 'video/mp4', 'webm' => 'video/webm', 'ogv' => 'video/ogg', 'mov' => 'video/quicktime',
    ];

    private const array AUDIO = [
        'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'oga' => 'audio/ogg', 'opus' => 'audio/ogg',
        'm4a' => 'audio/mp4', 'aac' => 'audio/aac', 'flac' => 'audio/flac',
    ];

    private const array TEXT = [
        'txt', 'text', 'md', 'markdown', 'rst', 'csv', 'tsv', 'log', 'json', 'jsonl', 'xml', 'yml', 'yaml', 'toml',
        'ini', 'cfg', 'conf', 'properties', 'env', 'sql', 'html', 'htm', 'xhtml', 'css', 'scss', 'sass', 'less',
        'js', 'mjs', 'cjs', 'ts', 'tsx', 'jsx', 'vue', 'svelte', 'php', 'phtml', 'twig', 'blade', 'py', 'rb', 'go',
        'rs', 'java', 'kt', 'kts', 'scala', 'c', 'h', 'cpp', 'cc', 'hpp', 'cs', 'swift', 'm', 'sh', 'bash', 'zsh',
        'fish', 'ps1', 'bat', 'cmd', 'lua', 'pl', 'r', 'dart', 'ex', 'exs', 'erl', 'hs', 'clj', 'graphql', 'gql',
        'proto', 'gradle', 'lock', 'diff', 'patch', 'srt', 'vtt', 'tex', 'bib', 'gitignore', 'gitattributes',
        'editorconfig', 'htaccess', 'dockerfile', 'makefile', 'tf', 'hcl', 'nginx',
    ];

    private const array TEXT_FILE_NAMES = ['dockerfile', 'makefile', 'license', 'readme', 'changelog', 'procfile', 'gemfile', 'vagrantfile'];

    public function __construct(private int $maxTextBytes)
    {
    }

    public function resolve(Entry $entry): ?PreviewType
    {
        if (!$entry->isFile()) {
            return null;
        }
        $extension = $entry->extension();

        return match (true) {
            isset(self::IMAGE[$extension]) => PreviewType::Image,
            isset(self::VIDEO[$extension]) => PreviewType::Video,
            isset(self::AUDIO[$extension]) => PreviewType::Audio,
            $extension === 'pdf' => PreviewType::Pdf,
            $this->isText($entry) && ($entry->size === null || $entry->size <= $this->maxTextBytes) => PreviewType::Text,
            default => null,
        };
    }

    /**
     * Content-Type for serving the file inline in a preview.
     */
    public function contentType(Entry $entry, PreviewType $type): string
    {
        $extension = $entry->extension();

        return match ($type) {
            PreviewType::Image => self::IMAGE[$extension],
            PreviewType::Video => self::VIDEO[$extension],
            PreviewType::Audio => self::AUDIO[$extension],
            PreviewType::Pdf => 'application/pdf',
            PreviewType::Text => 'text/plain; charset=utf-8',
        };
    }

    public function isTooLargeForText(Entry $entry): bool
    {
        return $this->isText($entry) && $entry->size !== null && $entry->size > $this->maxTextBytes;
    }

    private function isText(Entry $entry): bool
    {
        $extension = $entry->extension();
        if ($extension !== '' && in_array($extension, self::TEXT, true)) {
            return true;
        }
        if ($extension === '' && in_array(mb_strtolower($entry->name()), self::TEXT_FILE_NAMES, true)) {
            return true;
        }
        if ($extension === '' && str_starts_with($entry->name(), '.')) {
            return in_array(mb_strtolower(substr($entry->name(), 1)), self::TEXT, true);
        }

        return $entry->mimeType !== null && str_starts_with($entry->mimeType, 'text/');
    }
}
