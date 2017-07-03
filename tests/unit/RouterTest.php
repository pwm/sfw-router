<?php
declare(strict_types = 1);

namespace SFW\Router;

use PHPUnit\Framework\TestCase;
use SFW\Request\RequestMethod as Method;
use SFW\Request\RequestUri as Uri;

/**
 * @group router
 */
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
    public function itResolvesTheHandlerForARoute(): void
    {
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo/bar')), new ClassHandler('Foo', 'bar'));

        /** @var ClassHandler $routeHandler */
        $routeHandler = $this->router->resolve(new Route(new Method(Method::GET), new Uri('/foo/bar')));

        static::assertInstanceOf(ClassHandler::class, $routeHandler);
        static::assertSame('Foo', $routeHandler->getClassName());
        static::assertSame('bar', $routeHandler->getMethodName());
        static::assertSame('GET/foo/bar', $routeHandler->getRoute()->getRouteKey());
    }

    /**
     * @test
     */
    public function itResolvesTheCorrectHandlerForARoute(): void
    {
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo/bar1')), new ClassHandler('Foo', 'bar1'));
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo/bar2')), new ClassHandler('Foo', 'bar2'));
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo/bar3')), new ClassHandler('Foo', 'bar3'));

        /** @var ClassHandler $routeHandler */
        $routeHandler = $this->router->resolve(new Route(new Method(Method::GET), new Uri('/foo/bar2')));

        static::assertSame('Foo', $routeHandler->getClassName());
        static::assertSame('bar2', $routeHandler->getMethodName());
    }

    /**
     * @test
     */
    public function itResolvesTheHandlerForARouteWithCapturedSegments(): void
    {
        $this->router->add(new Route(new Method(Method::GET), new Uri('/a/{b}/c/{d}/e/{f}')), new ClassHandler('Foo', 'bar'));

        /** @var ClassHandler $routeHandler */
        $routeHandler = $this->router->resolve(new Route(new Method(Method::GET), new Uri('/a/1/c/2/e/3')));

        static::assertSame('Foo', $routeHandler->getClassName());
        static::assertSame('bar', $routeHandler->getMethodName());
        static::assertSame(['1', '2', '3'], $routeHandler->getRoute()->getCapturedSegments());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp 'No handler found for route .*'
     */
    public function itThrowsWhenNoHandlerFoundForRoute(): void
    {
        $this->router->resolve(new Route(new Method(Method::GET), new Uri('/foo/bar')));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Duplicate route definition detected.
     */
    public function itDetectsCollisionsForNormalRoutes(): void
    {
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo')), new ClassHandler('Foo', 'bar1'));
        $this->router->add(new Route(new Method(Method::GET), new Uri('/foo')), new ClassHandler('Foo', 'bar2'));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Duplicate route definition detected.
     */
    public function itDetectsCollisionsForRoutesWithCaptures(): void
    {
        $this->router->add(new Route(new Method(Method::GET), new Uri('/{foo}')), new ClassHandler('Foo', 'bar1'));
        $this->router->add(new Route(new Method(Method::GET), new Uri('/{foo}')), new ClassHandler('Foo', 'bar2'));
    }

    /**
     * @test
     */
    public function sameUriWithDifferentMethods(): void
    {
        $uri = new Uri('/foo/bar');
        $this->router->add(new Route(new Method(Method::GET),    $uri), new ClassHandler('X', 'getX'));
        $this->router->add(new Route(new Method(Method::POST),   $uri), new ClassHandler('X', 'postX'));
        $this->router->add(new Route(new Method(Method::PUT),    $uri), new ClassHandler('X', 'putX'));
        $this->router->add(new Route(new Method(Method::PATCH),  $uri), new ClassHandler('X', 'patchX'));
        $this->router->add(new Route(new Method(Method::DELETE), $uri), new ClassHandler('X', 'deleteX'));

        static::assertSame('getX',    $this->router->resolve(new Route(new Method(Method::GET),    $uri))->getMethodName());
        static::assertSame('postX',   $this->router->resolve(new Route(new Method(Method::POST),   $uri))->getMethodName());
        static::assertSame('putX',    $this->router->resolve(new Route(new Method(Method::PUT),    $uri))->getMethodName());
        static::assertSame('patchX',  $this->router->resolve(new Route(new Method(Method::PATCH),  $uri))->getMethodName());
        static::assertSame('deleteX', $this->router->resolve(new Route(new Method(Method::DELETE), $uri))->getMethodName());
    }

    /**
     * @test
     */
    public function randomSetOfUris(): void
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
        static::assertTrue($found);
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
