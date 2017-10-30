<?php
declare(strict_types=1);

namespace SFW\Router;

use PHPUnit\Framework\TestCase;
use SFW\Request\RequestMethod as Method;
use SFW\Request\RequestUri as Uri;

class RouterTest extends TestCase
{
    /** @var Router */
    private $router;

    public function setUp(): void
    {
        $this->router = new Router();
    }

    /**
     * @test
     */
    public function it_resolves_the_handler_for_a_route(): void
    {
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo/bar')), new ClassHandler('Foo', 'bar'));

        /** @var ClassHandler $routeHandler */
        $routeHandler = $this->router->resolve(new Route(new Method(Method::GET), new Uri('/foo/bar')));

        self::assertInstanceOf(ClassHandler::class, $routeHandler);
        self::assertSame('Foo', $routeHandler->getClassName());
        self::assertSame('bar', $routeHandler->getMethodName());
        self::assertSame('GET/foo/bar', $routeHandler->getRoute()->getRouteKey());
    }

    /**
     * @test
     */
    public function it_resolves_the_correct_handler_for_a_route(): void
    {
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo/bar1')), new ClassHandler('Foo', 'bar1'));
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo/bar2')), new ClassHandler('Foo', 'bar2'));
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo/bar3')), new ClassHandler('Foo', 'bar3'));

        /** @var ClassHandler $routeHandler */
        $routeHandler = $this->router->resolve(new Route(new Method(Method::GET), new Uri('/foo/bar2')));

        self::assertSame('Foo', $routeHandler->getClassName());
        self::assertSame('bar2', $routeHandler->getMethodName());
    }

    /**
     * @test
     */
    public function it_resolves_the_handler_for_a_route_with_captured_segments(): void
    {
        $this->router->add(new Route(new Method(Method::GET), new Uri('/a/{b}/c/{d}/e/{f}')), new ClassHandler('Foo', 'bar'));

        /** @var ClassHandler $routeHandler */
        $routeHandler = $this->router->resolve(new Route(new Method(Method::GET), new Uri('/a/1/c/2/e/3')));

        self::assertSame('Foo', $routeHandler->getClassName());
        self::assertSame('bar', $routeHandler->getMethodName());
        self::assertSame(['1', '2', '3'], $routeHandler->getRoute()->getCapturedSegments());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp 'No handler found for route .*'
     */
    public function it_throws_when_no_handler_found_for_route(): void
    {
        $this->router->resolve(new Route(new Method(Method::GET), new Uri('/foo/bar')));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Duplicate route definition detected.
     */
    public function it_detects_collisions_for_normal_routes(): void
    {
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo')), new ClassHandler('Foo', 'bar1'));
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo')), new ClassHandler('Foo', 'bar2'));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Duplicate route definition detected.
     */
    public function it_detects_collisions_for_routes_with_captures(): void
    {
        $this->router->add(new Route(new Method(Method::GET), new Uri('/{foo}')), new ClassHandler('Foo', 'bar1'));
        $this->router->add(new Route(new Method(Method::GET), new Uri('/{foo}')), new ClassHandler('Foo', 'bar2'));
    }

    /**
     * @test
     */
    public function it_resolves_the_same_uri_with_different_methods(): void
    {
        $uri = new Uri('/foo/bar');
        $this->router->add(new Route(new Method(Method::GET),    $uri), new ClassHandler('X', 'getX'));
        $this->router->add(new Route(new Method(Method::POST),   $uri), new ClassHandler('X', 'postX'));
        $this->router->add(new Route(new Method(Method::PUT),    $uri), new ClassHandler('X', 'putX'));
        $this->router->add(new Route(new Method(Method::PATCH),  $uri), new ClassHandler('X', 'patchX'));
        $this->router->add(new Route(new Method(Method::DELETE), $uri), new ClassHandler('X', 'deleteX'));

        self::assertSame('getX',    $this->router->resolve(new Route(new Method(Method::GET),    $uri))->getMethodName());
        self::assertSame('postX',   $this->router->resolve(new Route(new Method(Method::POST),   $uri))->getMethodName());
        self::assertSame('putX',    $this->router->resolve(new Route(new Method(Method::PUT),    $uri))->getMethodName());
        self::assertSame('patchX',  $this->router->resolve(new Route(new Method(Method::PATCH),  $uri))->getMethodName());
        self::assertSame('deleteX', $this->router->resolve(new Route(new Method(Method::DELETE), $uri))->getMethodName());
    }

    /**
     * @test
     */
    public function it_resolves_a_random_set_of_uris(): void
    {
        $numberOfRoutes = 100;
        $numberOfCalls = 10000;

        /** @var Route[] $routes */
        $routes = self::generateRandomRoutes($numberOfRoutes);
        foreach($routes as $k => $route) {
            $this->router->add($route, new ClassHandler('Foo', (string)$k));
        }

        $found = true;
        for ($i = 0; $i < $numberOfCalls; $i++) {
            $randomRouteIndex = random_int(0, $numberOfRoutes - 1);
            $randomTestRoute = new Route(
                $routes[$randomRouteIndex]->getMethod(),
                // the "filler" segments should be distinct, ie. numbers instead of characters, to avoid
                // the following: /*/b -fills-> /a/b -finds-> /a/* vs. /*/b
                new Uri(preg_replace('#\{.?+\}#', random_int(1, 1000), $routes[$randomRouteIndex]->getUri()->getData()))
            );
            $found = $found && $randomRouteIndex === (int)$this->router->resolve($randomTestRoute)->getMethodName();
        }
        self::assertTrue($found);
    }

    private static function generateRandomRoutes(int $amount, float $normalSegmentRatio = 0.7): array
    {
        $routes = [];
        do {
            $methodString = array_rand(array_flip(Method::VALID_METHODS));
            $uriString = '';
            for ($i = 0; $i < 6; $i++) {
                // make $normalSegmentRatio *100 % normal the rest wildcard
                $uriString .= random_int(1, 10) <= $normalSegmentRatio * 10
                    ? '/'.self::generateRandomString()
                    : '/{'.self::generateRandomString().'}';
            }
            $route = new Route(new Method($methodString), new Uri($uriString));
            if (! array_key_exists($route->getRouteKey(), $routes)) {
                $routes[$route->getRouteKey()] = new Route(new Method($methodString), new Uri($uriString));
            }
        } while (count($routes) < $amount);
        return array_values($routes);
    }

    private static function generateRandomString(int $length = 1): string
    {
        $letters = 'a b c d e f g h i j k l m n o p q r s t u v w x y z';
        $randomStringOrArray = array_rand(array_flip(explode(' ', $letters)), $length);
        return is_array($randomStringOrArray)
            ? implode('', $randomStringOrArray)
            : $randomStringOrArray;
    }
}
