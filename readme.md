# SFW Router

[![Build Status](https://travis-ci.org/pwm/sfw-router.svg?branch=master)](https://travis-ci.org/pwm/sfw-router)
[![Maintainability](https://api.codeclimate.com/v1/badges/53b26ce7f86af460d007/maintainability)](https://codeclimate.com/github/pwm/sfw-router/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/53b26ce7f86af460d007/test_coverage)](https://codeclimate.com/github/pwm/sfw-router/test_coverage)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A simple Router that maps incoming requests to predefined handlers.

It builds a tree using segments of a predefined uri as internal nodes and its corresponding handler as the terminal node. It resolves routes by traversing this tree.

Wildcard segments are supported and captured for use in the handler. See usage for more detail.

Exact segment match wins over wildcard match. Eg. if you have `/foo/bar` and `/foo/{x}` defined as routes with corresponding handlers `Bar` and `X` then `/foo/bar` will be handled by `Bar` while `/foo/baz` will be handled by `X`.

## Requirements

PHP 7.1+

## Installation

    composer require pwm/sfw-router

## Usage

```php
// Router depends on Request
use SFW\Request\Request;
use SFW\Request\RequestMethod as Method;
use SFW\Request\RequestUri as Uri;

// Have some controllers
class FooCtrl
{
    public function getAll(Request $request): array { /* ... */ }
    public function post(Request $request): bool { /* ... */ }
}
class BarCtrl
{
    public function getById(Request $request, $fooId, $barId): Bar { /* ... */ }
}

// Create router
$router = new Router();

// Add routes and corresponding route handlers
$router->add(new Route(new Method(Method::GET), new Uri('/foo')), new RouteHandler(FooCtrl::class, 'getAll'));
$router->add(new Route(new Method(Method::POST), new Uri('/foo')), new RouteHandler(FooCtrl::class, 'post'));
$router->add(new Route(new Method(Method::GET), new Uri('/foo/{id}/bar/{id}')), new RouteHandler(BarCtrl::class, 'getById'));

// Resolve a handler for an incoming request
$routeHandler = $router->resolve(new Route($request->getMethod(), $request->getUri()));

// (Optional) Resolve the handler class from the container and call the handling method 
$response = $container
    ->resolve($routeHandler->getClassName())
    ->{$routeHandler->getMethodName()}($request, ...$routeHandler->getRoute()->getCapturedSegments());
```

## How it works

TBD

## Tests

	$ vendor/bin/phpunit
	$ composer phpcs
	$ composer phpstan

## Changelog

[Click here](changelog.md)
