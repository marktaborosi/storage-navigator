<?php

namespace Marktaborosi\StorageNavigator\Tests\Unit\Renderers;

use PHPUnit\Framework\TestCase;
use Marktaborosi\StorageNavigator\Renderers\ConsoleRenderer;
use Marktaborosi\StorageNavigator\Renderers\Navigators\NullNavigationHandler;

class ConsoleRendererTest extends TestCase
{


    /**
     * Test that navigationHandler() returns a NullNavigationHandler instance.
     */
    public function test_navigation_handler_returns_null_navigation_handler()
    {
        // Create an instance of ConsoleRenderer
        $consoleRenderer = new ConsoleRenderer();

        // Assert that the navigationHandler() method returns an instance of NullNavigationHandler
        $this->assertInstanceOf(NullNavigationHandler::class, $consoleRenderer->navigationHandler());
    }
}