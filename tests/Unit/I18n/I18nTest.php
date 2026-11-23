<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Unit\I18n;

use Marktaborosi\StorageNavigator\I18n\Formatter;
use Marktaborosi\StorageNavigator\I18n\Translator;
use PHPUnit\Framework\TestCase;

final class I18nTest extends TestCase
{
    public function testEveryLocaleHasEveryKey(): void
    {
        $english = self::keys('en');
        foreach (Translator::availableLocales() as $locale) {
            $keys = self::keys($locale);
            self::assertSame([], array_values(array_diff($english, $keys)), "Missing keys in $locale");
            self::assertSame([], array_values(array_diff($keys, $english)), "Unknown keys in $locale");
        }
    }

    /**
     * @return list<array-key>
     */
    private static function keys(string $locale): array
    {
        $messages = require dirname(__DIR__, 3) . '/resources/translations/' . $locale . '.php';
        self::assertIsArray($messages);

        return array_keys($messages);
    }

    public function testParametersFallbackAndOverrides(): void
    {
        $translator = new Translator('hu', ['action.upload' => 'Fájlok fel']);

        self::assertSame('„a” törölve.', $translator->trans('toast.deleted', ['name' => 'a']));
        self::assertSame('Fájlok fel', $translator->trans('action.upload'));
        self::assertSame('Upload', (new Translator('xx'))->trans('action.upload'));
        self::assertSame('Upload', (new Translator('../../etc'))->trans('action.upload'));
        self::assertSame('missing.key', $translator->trans('missing.key'));
    }

    public function testBytes(): void
    {
        $formatter = new Formatter('Y', 'UTC');

        self::assertSame('0 B', $formatter->bytes(0));
        self::assertSame('1023 B', $formatter->bytes(1023));
        self::assertSame('1.5 KB', $formatter->bytes(1536));
        self::assertSame('12 MB', $formatter->bytes(12 * 1024 * 1024 + 1000));
        self::assertSame('', $formatter->bytes(null));
        self::assertSame('1,5 KB', (new Formatter('Y', 'UTC', ','))->bytes(1536));
    }

    public function testDates(): void
    {
        $formatter = new Formatter('Y-m-d H:i', 'Europe/Budapest');

        self::assertSame('2026-01-01 01:00', $formatter->date(1767225600));
        self::assertSame('2026-01-01T01:00:00+01:00', $formatter->isoDate(1767225600));
        self::assertSame('', $formatter->date(null));
    }
}
