<?php
declare(strict_types = 1);

namespace SFW\Router;

class RouteHandler
{
    /** @var string */
    private $className;

    /** @var string */
    private $methodName;

    private $route;

    public function __construct(string $className, string $methodName)
    {
        $this->className = $className;
        $this->methodName = $methodName;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function setRoute(Route $route): void
    {
        $this->route = $route;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }
}
