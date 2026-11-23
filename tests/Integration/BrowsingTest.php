<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Tests\Integration;

use Marktaborosi\StorageNavigator\Features;
use Marktaborosi\StorageNavigator\Filter\Filter;
use Marktaborosi\StorageNavigator\Options;
use Marktaborosi\StorageNavigator\Rendering\TextRenderer;
use Marktaborosi\StorageNavigator\Rendering\Theme;
use Marktaborosi\StorageNavigator\Tests\Support\NavigatorTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class BrowsingTest extends NavigatorTestCase
{
    public function testListsTheRoot(): void
    {
        $response = $this->get($this->navigator());
        $html = $this->body($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('data-name="readme.md"', $html);
        self::assertStringContainsString('data-name="docs"', $html);
        self::assertStringNotContainsString('.env', $html, 'Dotfiles are hidden by default');
        self::assertStringNotContainsString('.git', $html);
    }

    public function testListsASubdirectoryWithBreadcrumbs(): void
    {
        $html = $this->body($this->get($this->navigator(), ['path' => 'docs/nested']));

        self::assertStringContainsString('data-name="deep.txt"', $html);
        self::assertStringContainsString('href="?path=docs"', $html);
        self::assertStringContainsString('aria-current="page">nested<', $html);
    }

    #[DataProvider('themes')]
    public function testEveryThemeRenders(string $theme): void
    {
        $response = $this->get($this->navigator(new Options(theme: $theme, features: Features::all(), csrfSecret: self::SECRET)), ['path' => 'docs']);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('data-name="report.pdf"', $this->body($response));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function themes(): iterable
    {
        foreach (Theme::names() as $name) {
            yield $name => [$name];
        }
    }

    public function testHungarianInterface(): void
    {
        $html = $this->body($this->get($this->navigator(new Options(locale: 'hu'))));

        self::assertStringContainsString('lang="hu"', $html);
        self::assertStringContainsString('Keresés ebben a mappában', $html);
    }

    public function testHtmlPagesCarryAStrictContentSecurityPolicy(): void
    {
        $response = $this->get($this->navigator());
        $html = $this->body($response);
        $csp = $response->getHeaderLine('Content-Security-Policy');

        self::assertStringContainsString("script-src 'self'", $csp);
        self::assertStringNotContainsString('unsafe-inline', $csp);
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        // No inline event handlers and no inline script: everything runs from navigator.js.
        self::assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        self::assertDoesNotMatchRegularExpression('/<script(?![^>]*(\bsrc=|type="application\/json"))/i', $html);
    }

    public function testFileNamesAreEscapedEverywhere(): void
    {
        // Regression for the 2.x stored XSS: a name breaking out of the onClick JavaScript string.
        $evil = "x');alert(document.domain);('<img src=x onerror=alert(1)>\".txt";
        $this->filesystem->write($evil, 'x');

        $html = $this->body($this->get($this->navigator()));

        self::assertStringNotContainsString('<img src=x', $html);
        self::assertStringNotContainsString("alert(document.domain);('", $html);
        self::assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }

    public function testConfigJsonCannotBreakOutOfItsScriptTag(): void
    {
        $html = $this->body($this->get($this->navigator(new Options(title: '</script><script>alert(1)</script>'))));

        preg_match('#<script type="application/json" id="sn-config">(.*?)</script>#s', $html, $match);
        self::assertNotEmpty($match);
        self::assertStringNotContainsString('<', $match[1]);
    }

    public function testFilterHidesEntriesAndEverythingBelowThem(): void
    {
        $navigator = $this->navigator(new Options(filter: Filter::not(Filter::path('private', 'private/*'))));

        self::assertStringNotContainsString('data-name="private"', $this->body($this->get($navigator)));
        self::assertSame(404, $this->get($navigator, ['path' => 'private'])->getStatusCode());
        self::assertSame(404, $this->get($navigator, ['action' => 'download', 'path' => 'private/secret.txt'])->getStatusCode());
    }

    public function testShowHidden(): void
    {
        $html = $this->body($this->get($this->navigator(new Options(showHidden: true))));

        self::assertStringContainsString('data-name=".env"', $html);
    }

    public function testSortingAndSearch(): void
    {
        $navigator = $this->navigator();
        $html = $this->body($this->get($navigator, ['sort' => 'name', 'order' => 'desc']));
        self::assertLessThan(strpos($html, 'data-name="docs"'), strpos($html, 'data-name="private"'));

        $html = $this->body($this->get($navigator, ['q' => 'READ']));
        self::assertStringContainsString('data-name="readme.md"', $html);
        self::assertStringNotContainsString('data-name="notes.txt"', $html);
    }

    public function testJsonListing(): void
    {
        $data = $this->json($this->get($this->navigator(), ['path' => 'docs'], ['Accept' => 'application/json']));

        self::assertSame('docs', $data['path']);
        self::assertSame(['nested', 'logo.svg', 'page.html', 'report.pdf'], array_column($data['entries'], 'name'));
    }

    public function testDisabledNavigationKeepsUsersAtTheRoot(): void
    {
        $navigator = $this->navigator(new Options(features: new Features(navigation: false)));

        self::assertSame(200, $this->get($navigator)->getStatusCode());
        self::assertSame(403, $this->get($navigator, ['path' => 'docs'])->getStatusCode());
    }

    public function testAuthorizeCallbackIsConsulted(): void
    {
        $seen = [];
        $navigator = $this->navigator(new Options(authorize: static function ($operation, $path) use (&$seen): bool {
            $seen[] = $operation->value . ':' . $path;

            return (string) $path !== 'docs';
        }));

        self::assertSame(403, $this->get($navigator, ['path' => 'docs'])->getStatusCode());
        self::assertContains('list:docs', $seen);
    }

    public function testErrorsAsJsonForScripts(): void
    {
        $response = $this->get($this->navigator(), ['path' => 'missing'], ['Accept' => 'application/json']);

        self::assertSame(404, $response->getStatusCode());
        self::assertFalse($this->json($response)['ok']);
    }

    public function testUnknownActionAndWrongMethods(): void
    {
        $navigator = $this->navigator();

        self::assertSame(400, $this->get($navigator, ['action' => 'shell'])->getStatusCode());
        self::assertSame(405, $this->get($navigator, ['action' => 'delete', 'path' => 'notes.txt'])->getStatusCode());
        self::assertSame(405, $navigator->handle($this->request('PUT', '/'))->getStatusCode());
    }

    public function testHeadRequestsHaveNoBody(): void
    {
        $response = $this->navigator()->handle($this->request('HEAD', '/files'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $this->body($response));
    }

    public function testAssets(): void
    {
        $navigator = $this->navigator();
        $css = $this->get($navigator, ['action' => 'asset', 'name' => 'modern.css']);

        self::assertSame(200, $css->getStatusCode());
        self::assertStringStartsWith('text/css', $css->getHeaderLine('Content-Type'));
        self::assertNotSame('', $css->getHeaderLine('ETag'));
        self::assertSame(304, $this->get($navigator, ['action' => 'asset', 'name' => 'modern.css'], ['If-None-Match' => $css->getHeaderLine('ETag')])->getStatusCode());
        self::assertSame(404, $this->get($navigator, ['action' => 'asset', 'name' => '../composer.json'])->getStatusCode());
        self::assertSame(404, $this->get($navigator, ['action' => 'asset', 'name' => 'missing.js'])->getStatusCode());
    }

    public function testProgrammaticListingAndTextRenderer(): void
    {
        $listing = $this->navigator()->listing('docs');
        $text = (new TextRenderer(timezone: 'UTC'))->render($listing);

        self::assertCount(4, $listing);
        self::assertStringContainsString('nested/', $text);
        self::assertStringContainsString('<DIR>', $text);
        self::assertStringContainsString('1 folders, 3 files', $text);
    }
}
