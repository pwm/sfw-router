<?php
declare(strict_types = 1);

namespace SFW\Router;

use PHPUnit\Framework\TestCase;
use SFW\Request\RequestMethod;
use SFW\Request\RequestUri;

/**
 * @group router
 */
class CallableHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function itCreates(): void
    {
        $routeHandler = new CallableHandler(function ($x) { return sprintf('Called: %s', $x); });

        static::assertInstanceOf(RouteHandler::class, $routeHandler);
        static::assertInstanceOf(CallableHandler::class, $routeHandler);
        static::assertStringStartsWith('Called: foo', $routeHandler->getCallable()('foo'));

        $routeHandler->setRoute(new Route(new RequestMethod('GET'), new RequestUri('/foo/bar')));

        static::assertInstanceOf(Route::class, $routeHandler->getRoute());
    }
}
