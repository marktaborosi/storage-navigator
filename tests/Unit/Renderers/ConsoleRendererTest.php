<?php

namespace Marktaborosi\Tests\Unit\Renderers;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Exception;
use Marktaborosi\StorageBrowser\Renderers\ConsoleRenderer;
use Marktaborosi\StorageBrowser\Renderers\Entities\RenderData;
use Marktaborosi\StorageBrowser\Renderers\Navigators\NullNavigationHandler;

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