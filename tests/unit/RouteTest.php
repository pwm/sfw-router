<?php
declare(strict_types = 1);

namespace SFW\Router;

use PHPUnit\Framework\TestCase;
use SFW\Request\RequestMethod as Method;
use SFW\Request\RequestUri as Uri;

/**
 * @group router
 */
class RouteTest extends TestCase
{
    /**
     * @test
     */
    public function itCreates(): void
    {
        $route = new Route(new Method(Method::GET), new Uri('/foo/bar'));

        static::assertInstanceOf(Route::class, $route);
        static::assertSame(Method::GET, $route->getMethod()->getData());
        static::assertSame('/foo/bar', $route->getUri()->getData());
        static::assertSame('GET/foo/bar', $route->getRouteKey());
        static::assertSame([Method::GET, 'foo', 'bar'], $route->getSegments());
    }

    /**
     * @test
     */
    public function itCreatesWithCapturedSegments(): void
    {
        $route = new Route(new Method(Method::GET), new Uri('/a/{b}/c/{d}'));

        static::assertInstanceOf(Route::class, $route);
        static::assertSame(Method::GET, $route->getMethod()->getData());
        static::assertSame('/a/{b}/c/{d}', $route->getUri()->getData());
        static::assertSame(sprintf('GET/a/%s/c/%s', Route::CAPTURED_SEGMENT, Route::CAPTURED_SEGMENT), $route->getRouteKey());
        static::assertSame([Method::GET, 'a', Route::CAPTURED_SEGMENT, 'c', Route::CAPTURED_SEGMENT], $route->getSegments());
    }

    /**
     * @test
     */
    public function canAddCapturedSegments(): void
    {
        $route = new Route(new Method(Method::GET), new Uri('/a/{b}/c/{d}'));

        $route->addCapturedSegment('b');
        $route->addCapturedSegment('d');

        static::assertSame(['b', 'd'], $route->getCapturedSegments());
    }
}
