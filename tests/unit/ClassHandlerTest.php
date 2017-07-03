<?php
declare(strict_types = 1);

namespace SFW\Router;

use PHPUnit\Framework\TestCase;
use SFW\Request\RequestMethod;
use SFW\Request\RequestUri;

/**
 * @group router
 */
class ClassHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function itCreates(): void
    {
        $routeHandler = new ClassHandler('Foo', 'bar');

        static::assertInstanceOf(RouteHandler::class, $routeHandler);
        static::assertInstanceOf(ClassHandler::class, $routeHandler);
        static::assertStringStartsWith('Foo', $routeHandler->getClassName());
        static::assertStringStartsWith('bar', $routeHandler->getMethodName());

        $routeHandler->setRoute(new Route(new RequestMethod('GET'), new RequestUri('/foo/bar')));

        static::assertInstanceOf(Route::class, $routeHandler->getRoute());
    }
}
