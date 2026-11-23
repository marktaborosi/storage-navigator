<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\View;

use Marktaborosi\StorageNavigator\Entry\Entry;

/**
 * Broad file category, used for icons, colours and the "Type" column.
 */
enum FileKind: string
{
    case Folder = 'folder';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case Pdf = 'pdf';
    case Archive = 'archive';
    case Code = 'code';
    case Text = 'text';
    case Spreadsheet = 'spreadsheet';
    case Document = 'document';
    case Presentation = 'presentation';
    case Font = 'font';
    case File = 'file';

    private const array EXTENSIONS = [
        'image' => ['png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'bmp', 'ico', 'svg', 'tif', 'tiff', 'heic', 'psd', 'raw'],
        'video' => ['mp4', 'm4v', 'webm', 'ogv', 'mov', 'avi', 'mkv', 'wmv', 'flv'],
        'audio' => ['mp3', 'wav', 'ogg', 'oga', 'opus', 'm4a', 'aac', 'flac', 'wma', 'mid', 'midi'],
        'pdf' => ['pdf'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz', 'zst', 'iso', 'dmg', 'jar', 'war', 'cab'],
        'code' => [
            'php', 'js', 'mjs', 'cjs', 'ts', 'tsx', 'jsx', 'vue', 'svelte', 'html', 'htm', 'css', 'scss', 'sass', 'less',
            'json', 'xml', 'yml', 'yaml', 'toml', 'sql', 'py', 'rb', 'go', 'rs', 'java', 'kt', 'c', 'h', 'cpp', 'hpp',
            'cs', 'swift', 'sh', 'bash', 'ps1', 'bat', 'twig', 'lua', 'dart', 'graphql', 'proto', 'ini', 'conf', 'env',
        ],
        'text' => ['txt', 'md', 'markdown', 'rst', 'log', 'rtf', 'tex', 'srt', 'vtt', 'nfo'],
        'spreadsheet' => ['xls', 'xlsx', 'xlsm', 'ods', 'csv', 'tsv', 'numbers'],
        'document' => ['doc', 'docx', 'odt', 'pages', 'epub', 'mobi'],
        'presentation' => ['ppt', 'pptx', 'odp', 'key'],
        'font' => ['ttf', 'otf', 'woff', 'woff2', 'eot'],
    ];

    public static function of(Entry $entry): self
    {
        if ($entry->isDirectory()) {
            return self::Folder;
        }
        $extension = $entry->extension();
        foreach (self::EXTENSIONS as $kind => $extensions) {
            if (in_array($extension, $extensions, true)) {
                return self::from($kind);
            }
        }

        return self::File;
    }
}
