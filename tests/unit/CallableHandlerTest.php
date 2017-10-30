<?php
declare(strict_types=1);

namespace SFW\Router;

use PHPUnit\Framework\TestCase;
use SFW\Request\RequestMethod;
use SFW\Request\RequestUri;

class CallableHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates(): void
    {
        $routeHandler = new CallableHandler(function ($x) { return sprintf('Called: %s', $x); });

        self::assertInstanceOf(RouteHandler::class, $routeHandler);
        self::assertInstanceOf(CallableHandler::class, $routeHandler);
        self::assertStringStartsWith('Called: foo', $routeHandler->getCallable()('foo'));

        $routeHandler->setRoute(new Route(new RequestMethod('GET'), new RequestUri('/foo/bar')));

        self::assertInstanceOf(Route::class, $routeHandler->getRoute());
    }
}
