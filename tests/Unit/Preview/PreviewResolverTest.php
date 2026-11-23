<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Unit\Preview;

use Marktaborosi\StorageNavigator\Entry\Entry;
use Marktaborosi\StorageNavigator\Path\Path;
use Marktaborosi\StorageNavigator\Preview\PreviewResolver;
use Marktaborosi\StorageNavigator\Preview\PreviewType;
use PHPUnit\Framework\TestCase;

final class PreviewResolverTest extends TestCase
{
    private PreviewResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new PreviewResolver(1000);
    }

    private function type(string $name, ?int $size = 10): ?PreviewType
    {
        return $this->resolver->resolve(Entry::file(Path::fromString($name), $size));
    }

    public function testTypes(): void
    {
        self::assertSame(PreviewType::Image, $this->type('a.JPG'));
        self::assertSame(PreviewType::Video, $this->type('a.mp4'));
        self::assertSame(PreviewType::Audio, $this->type('a.mp3'));
        self::assertSame(PreviewType::Pdf, $this->type('a.pdf'));
        self::assertSame(PreviewType::Text, $this->type('a.php'));
        self::assertSame(PreviewType::Text, $this->type('Dockerfile'));
        self::assertSame(PreviewType::Text, $this->type('.gitignore'));
        self::assertNull($this->type('a.docx'));
        self::assertNull($this->resolver->resolve(Entry::directory(Path::fromString('a.txt'))));
    }

    public function testLargeTextHasNoPreview(): void
    {
        self::assertNull($this->type('big.log', 1001));
        self::assertTrue($this->resolver->isTooLargeForText(Entry::file(Path::fromString('big.log'), 1001)));
    }

    public function testMarkupIsServedAsPlainText(): void
    {
        $entry = Entry::file(Path::fromString('page.html'), 10);

        self::assertSame('text/plain; charset=utf-8', $this->resolver->contentType($entry, PreviewType::Text));
    }
}
