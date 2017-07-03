<?php
declare(strict_types = 1);

namespace SFW\Router;

class CallableHandler extends RouteHandler
{
    /** @var callable */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function getCallable(): callable
    {
        return $this->callable;
    }
}
