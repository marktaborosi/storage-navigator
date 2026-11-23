<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Unit\Http;

use Marktaborosi\StorageNavigator\Exception\RangeNotSatisfiableException;
use Marktaborosi\StorageNavigator\Http\ByteRange;
use Marktaborosi\StorageNavigator\Http\CallbackStream;
use Marktaborosi\StorageNavigator\Http\ContentDisposition;
use Marktaborosi\StorageNavigator\Http\ResourceStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HttpHelpersTest extends TestCase
{
    public function testContentDispositionEscapesNames(): void
    {
        self::assertSame(
            'attachment; filename="a_b_.txt"; filename*=UTF-8\'\'a%22b%5C.txt',
            ContentDisposition::attachment('a"b\\.txt'),
        );
        self::assertSame(
            'inline; filename="_rv_zt_r_.pdf"; filename*=UTF-8\'\'%C3%A1rv%C3%ADzt%C5%B1r%C5%91.pdf',
            ContentDisposition::inline('árvíztűrő.pdf'),
        );
        self::assertStringStartsWith('attachment; filename="download"', ContentDisposition::attachment('日本'));
    }

    #[DataProvider('ranges')]
    public function testByteRanges(string $header, ?array $expected): void
    {
        $range = ByteRange::parse($header, 1000);
        self::assertSame($expected, $range === null ? null : [$range->start, $range->end]);
    }

    /**
     * @return iterable<string, array{string, list<int>|null}>
     */
    public static function ranges(): iterable
    {
        yield 'none' => ['', null];
        yield 'bounded' => ['bytes=0-99', [0, 99]];
        yield 'open ended' => ['bytes=900-', [900, 999]];
        yield 'suffix' => ['bytes=-100', [900, 999]];
        yield 'end past size is clamped' => ['bytes=500-5000', [500, 999]];
        yield 'multi range is ignored' => ['bytes=0-1,5-6', null];
        yield 'garbage is ignored' => ['items=0-1', null];
    }

    public function testUnsatisfiableRange(): void
    {
        $this->expectException(RangeNotSatisfiableException::class);
        ByteRange::parse('bytes=1000-', 1000);
    }

    public function testResourceStreamWindow(): void
    {
        $resource = fopen('php://temp', 'w+b');
        \assert($resource !== false);
        fwrite($resource, '0123456789');
        rewind($resource);

        $stream = new ResourceStream($resource, 2, 5);
        self::assertSame(5, $stream->getSize());
        self::assertSame('234', $stream->read(3));
        self::assertSame('56', $stream->read(100));
        self::assertTrue($stream->eof());
        self::assertSame('23456', (string) $stream);
    }

    public function testCallbackStreamCanBeBuffered(): void
    {
        $stream = new CallbackStream(static function ($output): void {
            fwrite($output, 'zip bytes');
        });

        self::assertSame('zip bytes', $stream->getContents());
    }
}
