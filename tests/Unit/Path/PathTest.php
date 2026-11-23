<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Unit\Path;

use Marktaborosi\StorageNavigator\Exception\InvalidPathException;
use Marktaborosi\StorageNavigator\Path\Path;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PathTest extends TestCase
{
    #[DataProvider('normalizedPaths')]
    public function testNormalizes(string $input, string $expected): void
    {
        self::assertSame($expected, Path::fromString($input)->toString());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normalizedPaths(): iterable
    {
        yield 'empty is root' => ['', ''];
        yield 'slash is root' => ['/', ''];
        yield 'leading and trailing slashes' => ['/docs/a/', 'docs/a'];
        yield 'duplicate slashes' => ['docs//a///b', 'docs/a/b'];
        yield 'dot segments' => ['./docs/./a', 'docs/a'];
        yield 'backslashes' => ['docs\\a\\b.txt', 'docs/a/b.txt'];
        yield 'unicode' => ['Fényképek/árvíztűrő.jpg', 'Fényképek/árvíztűrő.jpg'];
        yield 'dots inside names' => ['a..b/c...d', 'a..b/c...d'];
    }

    #[DataProvider('maliciousPaths')]
    public function testRejectsUnsafePaths(string $input): void
    {
        $this->expectException(InvalidPathException::class);
        Path::fromString($input);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function maliciousPaths(): iterable
    {
        yield 'parent' => ['..'];
        yield 'parent prefix' => ['../etc/passwd'];
        yield 'parent in the middle' => ['docs/../../etc'];
        yield 'parent with backslashes' => ['docs\\..\\..\\secret'];
        yield 'absolute with parent' => ['/var/www/../../etc/passwd'];
        yield 'nul byte' => ["file.txt\0.jpg"];
        yield 'newline' => ["evil\nname"];
        yield 'escape' => ["evil\x1Bname"];
        yield 'delete char' => ["evil\x7Fname"];
        yield 'invalid utf-8' => ["\xC3\x28"];
        yield 'segment too long' => [str_repeat('a', 256)];
        yield 'path too long' => [str_repeat('a/', 2049)];
    }

    public function testAbsoluteLookingPathsStayInsideTheRoot(): void
    {
        self::assertSame('etc/passwd', Path::fromString('/etc/passwd')->toString());
        self::assertSame('C:/Windows', Path::fromString('C:\\Windows')->toString());
    }

    public function testNavigation(): void
    {
        $path = Path::fromString('a/b/c.TXT');

        self::assertSame('c.TXT', $path->name());
        self::assertSame('txt', $path->extension());
        self::assertSame('a/b', $path->parent()->toString());
        self::assertSame(['', 'a', 'a/b'], array_map('strval', $path->ancestors()));
        self::assertTrue($path->isDescendantOf(Path::fromString('a')));
        self::assertFalse(Path::fromString('ab/c')->isDescendantOf(Path::fromString('a')));
        self::assertSame('b/c.TXT', $path->relativeTo(Path::fromString('a')));
        self::assertTrue(Path::root()->parent()->isRoot());
        self::assertSame('a/b/d', $path->parent()->child('d')->toString());
    }

    public function testDotfilesHaveNoExtension(): void
    {
        self::assertSame('', Path::fromString('.env')->extension());
        self::assertSame('gz', Path::fromString('backup.tar.gz')->extension());
    }

    public function testChildRejectsSeparatorsAndParents(): void
    {
        $this->expectException(InvalidPathException::class);
        Path::root()->child('../x');
    }

    #[DataProvider('invalidNames')]
    public function testRejectsInvalidNames(string $name): void
    {
        $this->expectException(InvalidPathException::class);
        Path::assertValidName($name);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNames(): iterable
    {
        yield 'empty' => [''];
        yield 'slash' => ['a/b'];
        yield 'backslash' => ['a\\b'];
        yield 'dot' => ['.'];
        yield 'dot dot' => ['..'];
        yield 'trailing dot' => ['report.'];
        yield 'leading space' => [' report'];
        yield 'trailing space' => ['report '];
        yield 'control character' => ["re\tport"];
    }

    public function testAcceptsOrdinaryNames(): void
    {
        Path::assertValidName('Annual report (final) – 2026.pdf');
        Path::assertValidName('.gitignore');
        $this->addToAssertionCount(2);
    }
}
