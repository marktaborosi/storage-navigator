<?php

namespace Marktaborosi\Tests\Unit\Renderers;

use Marktaborosi\StorageBrowser\Config\FileBrowserConfig;
use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\StorageBrowser\Renderers\Entities\RenderData;
use Marktaborosi\StorageBrowser\Renderers\HtmlRenderer;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class HtmlRendererTest
 *
 * This class contains unit tests for the `HtmlRenderer` class, verifying its behavior
 * with existing and non-existing themes, its rendering capabilities, and error handling.
 *
 * @package Marktaborosi\Tests\Unit\Renderers
 */
class HtmlRendererTest extends TestCase
{
    private string $testFilePath;

    /**
     * Set up the test environment.
     *
     * Creates a temporary theme file to test constructor behavior with an existing file
     * and mocks the Twig environment.
     *
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->testFilePath = __DIR__ . "/../../Storage/theme.css";
        file_put_contents($this->testFilePath, 'body { background: #000 }');

        $this->twigMock = $this->createMock(Environment::class);
    }

    /**
     * Tear down the test environment.
     *
     * Cleans up temporary files created during the tests to ensure no data leakage.
     */
    protected function tearDown(): void
    {
        $files = glob(__DIR__ . "/../../Storage" . DIRECTORY_SEPARATOR . '*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Tests the constructor when the theme file exists.
     *
     * Verifies that the constructor correctly initializes private properties
     * when provided with an existing theme file.
     */
    public function test_constructor_with_existing_theme(): void
    {
        $htmlRenderer = new HtmlRenderer(
            $this->testFilePath,
            true,
            false
        );

        $reflection = new ReflectionClass($htmlRenderer);

        $themeHtmlPathProperty = $reflection->getProperty('themeHtmlPath');
        $themeHtmlPath = $themeHtmlPathProperty->getValue($htmlRenderer);
        $this->assertSame($this->testFilePath, $themeHtmlPath);

        $disableNavigationProperty = $reflection->getProperty('disableNavigation');
        $this->assertTrue($disableNavigationProperty->getValue($htmlRenderer));

        $disableFileDownloadProperty = $reflection->getProperty('disableFileDownload');
        $this->assertFalse($disableFileDownloadProperty->getValue($htmlRenderer));
    }

    /**
     * Tests the constructor when the theme file does not exist.
     *
     * Verifies that the constructor sets the `themeHtmlPath` to a default value
     * when the provided theme does not exist.
     */
    public function test_constructor_with_non_existing_theme(): void
    {
        $nonExistentTheme = 'nonexistent-theme';
        $expectedThemeHtmlPath = '/../public/css/' . $nonExistentTheme . ".min.css";

        $htmlRenderer = new HtmlRenderer(
            $nonExistentTheme,
            true,
            true
        );

        $reflection = new ReflectionClass($htmlRenderer);

        $themeHtmlPathProperty = $reflection->getProperty('themeHtmlPath');
        $themeHtmlPath = $themeHtmlPathProperty->getValue($htmlRenderer);
        $this->assertSame($expectedThemeHtmlPath, $themeHtmlPath);

        $disableNavigationProperty = $reflection->getProperty('disableNavigation');
        $this->assertTrue($disableNavigationProperty->getValue($htmlRenderer));

        $disableFileDownloadProperty = $reflection->getProperty('disableFileDownload');
        $this->assertTrue($disableFileDownloadProperty->getValue($htmlRenderer));
    }

    /**
     * Tests the rendering functionality of the `HtmlRenderer`.
     *
     * Mocks the Twig environment and ensures that the `render` method produces
     * the expected output with valid data.
     *
     * @throws SyntaxError
     * @throws Exception
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function test_render_renders_correctly(): void
    {
        $mockFileStructure = $this->createMock(FileStructure::class);
        $mockFileStructure->method('toArray')->willReturn([]);

        $mockConfig = $this->createMock(FileBrowserConfig::class);
        $mockConfig->method('get')->with('date_format')->willReturn('Y-m-d');

        $mockRenderData = $this->createMock(RenderData::class);
        $mockRenderData->method('getCurrentPath')->willReturn('/current/path');
        $mockRenderData->method('getRootPath')->willReturn('/root/path');
        $mockRenderData->method('getStructure')->willReturn($mockFileStructure);
        $mockRenderData->method('getConfiguration')->willReturn($mockConfig);

        $htmlRenderer = new HtmlRenderer(
            $this->testFilePath,
            false,
            false
        );

        $this->twigMock->expects($this->once())
            ->method('render')
            ->with(
                'layout.html.twig',
                $this->callback(function ($data) {
                    return $data['current_path'] === '/current/path' &&
                        $data['root_path'] === '/root/path' &&
                        $data['theme_path'] === $this->testFilePath &&
                        $data['files'] === [] &&
                        !$data['disable_navigation'] &&
                        !$data['disable_file_download'];
                })
            )
            ->willReturn('rendered content');

        $reflection = new ReflectionClass($htmlRenderer);
        $twigProperty = $reflection->getProperty('twig');
        $twigProperty->setValue($htmlRenderer, $this->twigMock);

        ob_start();
        $htmlRenderer->render($mockRenderData);
        $output = ob_get_clean();

        $this->assertEquals('rendered content', $output);
    }

    /**
     * Tests the `render` method when the theme is not found.
     *
     * Verifies that the `render` method gracefully handles the scenario where
     * the specified theme does not exist, rendering an appropriate error message.
     *
     * @throws SyntaxError
     * @throws Exception
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function test_render_handles_theme_not_found(): void
    {
        $mockRenderData = $this->createMock(RenderData::class);

        $htmlRenderer = new HtmlRenderer(
            'nonexistent-theme',
            false,
            false
        );

        $reflection = new ReflectionClass($htmlRenderer);
        $twigProperty = $reflection->getProperty('twig');
        $twigProperty->setValue($htmlRenderer, $this->twigMock);

        $this->twigMock->expects($this->once())
            ->method('render')
            ->with(
                'error/theme_not_found.html.twig',
                $this->callback(function ($data) {
                    return str_contains($data['theme_path'], '/../public/css/nonexistent-theme.min.css');
                })
            )
            ->willReturn('theme not found');

        ob_start();
        $htmlRenderer->render($mockRenderData);
        $output = ob_get_clean();

        $this->assertEquals('theme not found', $output);
        $this->assertSame(404, http_response_code());
    }
}
