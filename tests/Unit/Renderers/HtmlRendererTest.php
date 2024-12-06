<?php

namespace Marktaborosi\StorageNavigator\Tests\Unit\Renderers;


use Exception;
use Marktaborosi\StorageNavigator\Entities\FileStructure;
use Marktaborosi\StorageNavigator\Renderers\HtmlRenderer;
use Marktaborosi\StorageNavigator\Renderers\Config\HtmlRendererConfig;
use Marktaborosi\StorageNavigator\Renderers\Entities\RenderData;
use Marktaborosi\StorageNavigator\Interfaces\StorageNavigatorNavigationHandlerInterface;
use Marktaborosi\StorageNavigator\Renderers\Navigators\HttpNavigationHandler;
use Mockery;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

/**
 * Class HtmlRendererTest
 *
 * Tests the functionality of the HtmlRenderer class.
 */
class HtmlRendererTest extends TestCase
{
    /**
     * Test that the renderer is initialized correctly with a valid theme path.
     * @throws LoaderError If there is an issue loading templates.
     * @throws RuntimeError If a runtime error occurs.
     * @throws SyntaxError If there is a syntax error in a template.
     */
    public function test_renderer_initialization_with_valid_theme()
    {
        $themePath = __DIR__ . '/mock-theme.css';
        file_put_contents($themePath, 'body { background: #fff; }');

        $config = Mockery::mock(HtmlRendererConfig::class);
        $renderer = new HtmlRenderer($themePath, $config);

        $this->assertInstanceOf(HtmlRenderer::class, $renderer);

        unlink($themePath);
    }


    /**
     * Test navigationHandler method.
     * @throws LoaderError If there is an issue loading templates.
     * @throws RuntimeError If a runtime error occurs.
     * @throws SyntaxError If there is a syntax error in a template.
     */
    public function test_navigation_handler()
    {
        $themePath = __DIR__ . '/mock-theme.css';
        file_put_contents($themePath, 'body { background: #fff; }');

        $config = Mockery::mock(HtmlRendererConfig::class);
        $renderer = new HtmlRenderer($themePath, $config);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $handler = $renderer->navigationHandler();
        $this->assertInstanceOf(StorageNavigatorNavigationHandlerInterface::class, $handler);
        $this->assertInstanceOf(HttpNavigationHandler::class, $handler);

        unlink($themePath);
    }

    /**
     * Test getBackPath method for valid paths.
     * @throws LoaderError If there is an issue loading templates.
     * @throws RuntimeError If a runtime error occurs.
     * @throws SyntaxError If there is a syntax error in a template.
     */
    public function test_get_back_path()
    {
        $themePath = __DIR__ . '/mock-theme.css';
        file_put_contents($themePath, 'body { background: #fff; }');

        $config = Mockery::mock(HtmlRendererConfig::class);
        $renderer = new HtmlRenderer($themePath, $config);

        $backPath = $this->invokePrivateMethod($renderer, 'getBackPath', ['/current/path', '/root/path']);
        $this->assertEquals('/current', $backPath);

        $rootBackPath = $this->invokePrivateMethod($renderer, 'getBackPath', ['/root/path', '/root/path']);
        $this->assertNull($rootBackPath);

        unlink($themePath);
    }

    /**
     * Helper to invoke private methods for testing.
     *
     * @param object $object The object to invoke the method on.
     * @param string $methodName The name of the method to invoke.
     * @param array $parameters Parameters to pass to the method.
     * @return mixed The result of the method call.
     */
    private function invokePrivateMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Tear down Mockery after tests.
     */
    protected function tearDown(): void
    {
        Mockery::close();
    }
}