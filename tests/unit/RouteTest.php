<?php
declare(strict_types=1);

namespace SFW\Router;

use PHPUnit\Framework\TestCase;
use SFW\Request\RequestMethod as Method;
use SFW\Request\RequestUri as Uri;

class RouteTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates(): void
    {
        $route = new Route(new Method(Method::GET), new Uri('/foo/bar'));

        self::assertInstanceOf(Route::class, $route);
        self::assertSame(Method::GET, $route->getMethod()->getData());
        self::assertSame('/foo/bar', $route->getUri()->getData());
        self::assertSame('GET/foo/bar', $route->getRouteKey());
        self::assertSame([Method::GET, 'foo', 'bar'], $route->getSegments());
    }

    /**
     * @test
     */
    public function it_creates_with_captured_segments(): void
    {
        $route = new Route(new Method(Method::GET), new Uri('/a/{b}/c/{d}'));

        self::assertInstanceOf(Route::class, $route);
        self::assertSame(Method::GET, $route->getMethod()->getData());
        self::assertSame('/a/{b}/c/{d}', $route->getUri()->getData());
        self::assertSame(sprintf('GET/a/%s/c/%s', Route::CAPTURED_SEGMENT, Route::CAPTURED_SEGMENT), $route->getRouteKey());
        self::assertSame([Method::GET, 'a', Route::CAPTURED_SEGMENT, 'c', Route::CAPTURED_SEGMENT], $route->getSegments());
    }

    /**
     * @test
     */
    public function it_can_add_captured_segments(): void
    {
        $route = new Route(new Method(Method::GET), new Uri('/a/{b}/c/{d}'));

        $route->addCapturedSegment('b');
        $route->addCapturedSegment('d');

        self::assertSame(['b', 'd'], $route->getCapturedSegments());
    }
}
