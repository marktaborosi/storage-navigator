<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Unit\Security;

use Marktaborosi\StorageNavigator\Exception\ConfigurationException;
use Marktaborosi\StorageNavigator\Features;
use Marktaborosi\StorageNavigator\Limits;
use Marktaborosi\StorageNavigator\Options;
use Marktaborosi\StorageNavigator\Rendering\Theme;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OptionsTest extends TestCase
{
    public function testWriteFeaturesRequireACsrfSecret(): void
    {
        $this->expectException(ConfigurationException::class);
        new Options(features: new Features(upload: true));
    }

    public function testShortSecretsAreRejected(): void
    {
        $this->expectException(ConfigurationException::class);
        new Options(features: new Features(delete: true), csrfSecret: 'too-short');
    }

    public function testReadOnlyNeedsNoSecret(): void
    {
        self::assertFalse((new Options(features: Features::readOnly(zipDownload: true)))->features->hasWriteFeature());
    }

    public function testUnknownTimezone(): void
    {
        $this->expectException(ConfigurationException::class);
        new Options(timezone: 'Mars/Olympus');
    }

    public function testLimitsMustBePositive(): void
    {
        $this->expectException(ConfigurationException::class);
        new Limits(maxUploadBytes: 0);
    }

    #[DataProvider('fileNames')]
    public function testFileNameRules(string $name, bool $allowed): void
    {
        self::assertSame($allowed, (new Options())->isFileNameAllowed($name));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function fileNames(): iterable
    {
        yield 'image' => ['photo.jpg', true];
        yield 'no extension' => ['Makefile', true];
        yield 'php' => ['shell.php', false];
        yield 'upper-case php' => ['SHELL.PHP', false];
        yield 'phtml' => ['x.phtml', false];
        yield 'double extension' => ['shell.php.jpg', false];
        yield 'htaccess' => ['.htaccess', false];
        yield 'phar' => ['tool.phar', false];
    }

    public function testAllowList(): void
    {
        $options = new Options(allowedUploadExtensions: ['.PDF', 'png']);

        self::assertTrue($options->isFileNameAllowed('a.pdf'));
        self::assertTrue($options->isFileNameAllowed('a.PNG'));
        self::assertFalse($options->isFileNameAllowed('a.txt'));
        self::assertFalse($options->isFileNameAllowed('noextension'));
    }

    public function testThemes(): void
    {
        self::assertSame('console', (new Options(theme: 'hacker'))->theme->layout);
        self::assertSame('dark', Theme::named('modern-dark')->colorScheme);
        self::assertContains('norton-commander', Theme::names());

        $custom = Theme::custom('brand', Theme::LAYOUT_MODERN, ['modern.css', '/css/brand.css']);
        self::assertSame(['modern.css', '/css/brand.css'], (new Options(theme: $custom))->theme->stylesheets);
    }

    public function testLegacyThemeNamesStillWork(): void
    {
        self::assertSame('norton-commander', Theme::named('console-norton-commander')->name);
        self::assertSame('modern-dark', Theme::named('basic-dark')->name);
    }

    public function testUnknownTheme(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Available themes');
        new Options(theme: '../../etc/passwd');
    }
}
