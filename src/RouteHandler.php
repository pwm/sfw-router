<?php
declare(strict_types = 1);

namespace SFW\Router;

abstract class RouteHandler
{
    /** @var Route */
    private $route;

    public function setRoute(Route $route): void
    {
        $this->route = $route;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }
}
