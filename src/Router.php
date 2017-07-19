<?php
declare(strict_types = 1);

namespace SFW\Router;

use RuntimeException;

class Router
{
    /** @var RouteHandler[] */
    private $routes = [];

    /** @var Route[] */
    private $routeHandlers = [];

    /** @var RouteHandler[] */
    private $routeTree = [];

    private const ROUTE_HANDLER_KEY = 'RH';

    private const EXACT_MATCH = 'EM';
    private const WILDCARD_MATCH = 'WM';

    public function add(Route $route, RouteHandler $routeHandler): void
    {
        $this->preventRouteCollision($route->getRouteKey());
        $this->routes[$route->getRouteKey()] = $routeHandler;
        $this->routeHandlers[spl_object_hash($routeHandler)] = $route;
        $this->routeTree = self::mergeRoutePathIntoTree(
            self::createRoutePath($route->getSegments(), $routeHandler),
            $this->routeTree
        );
    }

    public function resolve(Route $route): RouteHandler
    {
        $matchTree = self::buildMatchTree($route->getSegments(), $this->routeTree);
        $bestMatch = self::selectBestMatch($matchTree);
        if ($bestMatch instanceof RouteHandler) {
            $matchedRouteSegments = $this->routeHandlers[spl_object_hash($bestMatch)]->getSegments();
            foreach ($matchedRouteSegments as $k => $segment) {
                if ($segment === Route::CAPTURED_SEGMENT) {
                    $route->addCapturedSegment($route->getSegments()[$k]);
                }
            }
            $bestMatch->setRoute($route);
            return $bestMatch;
        }
        throw new RuntimeException(sprintf('No handler found for route %s.', $route->getRouteKey()));
    }

    private function preventRouteCollision(string $routeString): void
    {
        // only prevents exact route collision, but allows eg. /*/a + /a/*
        if (array_key_exists($routeString, $this->routes)) {
            throw new RuntimeException('Duplicate route definition detected.');
        }
    }

    private static function createRoutePath(array $segments, RouteHandler $routeHandler, int $i = 0): array
    {
        if ($i === count($segments)) {
            return [self::ROUTE_HANDLER_KEY => $routeHandler];
        }
        $routePath[$segments[$i]] = self::createRoutePath($segments, $routeHandler, ++$i);
        return $routePath;
    }

    private static function mergeRoutePathIntoTree(array $routePath, array $routeTree): array
    {
        $rKey = key($routePath);
        $routeTree[$rKey] = array_key_exists($rKey, $routeTree)
            ? self::mergeRoutePathIntoTree($routePath[$rKey], $routeTree[$rKey])
            : $routePath[$rKey];
        return $routeTree;
    }

    private static function buildMatchTree(array $segments, array $routeTree)
    {
        $matchTree = [];
        $segment = array_shift($segments);
        if (isset($routeTree[$segment])) {
            $matchTree[self::EXACT_MATCH] = self::buildMatchTree($segments, $routeTree[$segment]);
        }
        if (isset($routeTree[Route::CAPTURED_SEGMENT])) {
            $matchTree[self::WILDCARD_MATCH] = self::buildMatchTree($segments, $routeTree[Route::CAPTURED_SEGMENT]);
        }
        return $segment === null && array_key_exists(self::ROUTE_HANDLER_KEY, $routeTree)
            ? $routeTree[self::ROUTE_HANDLER_KEY]
            : $matchTree;
    }

    private static function selectBestMatch(array $matchTree): ?RouteHandler
    {
        while (is_array($matchTree)) {
            $matchTree = $matchTree[self::EXACT_MATCH] ?? $matchTree[self::WILDCARD_MATCH] ?? null;
        }
        /** @var RouteHandler|null $matchTree */
        return $matchTree;
    }
}
