<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Http;

/**
 * Builds a Content-Disposition header that is safe for any file name (RFC 6266 / RFC 5987):
 * an ASCII fallback in "filename" plus the exact UTF-8 name in "filename*".
 */
final class ContentDisposition
{
    private function __construct()
    {
    }

    public static function attachment(string $fileName): string
    {
        return self::build('attachment', $fileName);
    }

    public static function inline(string $fileName): string
    {
        return self::build('inline', $fileName);
    }

    private static function build(string $type, string $fileName): string
    {
        $fallback = preg_replace('/[^\x20-\x7E]|["\\\\%]/u', '_', $fileName) ?? 'download';
        if (trim($fallback, '_ ') === '') {
            $fallback = 'download';
        }

        return sprintf('%s; filename="%s"; filename*=UTF-8\'\'%s', $type, $fallback, rawurlencode($fileName));
    }
}
