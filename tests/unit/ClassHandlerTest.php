<?php
declare(strict_types=1);

namespace SFW\Router;

use PHPUnit\Framework\TestCase;
use SFW\Request\RequestMethod;
use SFW\Request\RequestUri;

class ClassHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates(): void
    {
        $routeHandler = new ClassHandler('Foo', 'bar');

        self::assertInstanceOf(RouteHandler::class, $routeHandler);
        self::assertInstanceOf(ClassHandler::class, $routeHandler);
        self::assertStringStartsWith('Foo', $routeHandler->getClassName());
        self::assertStringStartsWith('bar', $routeHandler->getMethodName());

        $routeHandler->setRoute(new Route(new RequestMethod('GET'), new RequestUri('/foo/bar')));

        self::assertInstanceOf(Route::class, $routeHandler->getRoute());
    }
}
