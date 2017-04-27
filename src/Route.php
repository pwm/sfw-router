<?php
declare(strict_types = 1);

namespace SFW\Router;

use SFW\Request\RequestMethod as Method;
use SFW\Request\RequestUri as Uri;

class Route
{
    public const CAPTURED_SEGMENT = '*';

    /** @var Method */
    private $method;

    /** @var Uri */
    private $uri;

    /** @var string */
    private $routeKey;

    /** @var string[] */
    private $segments;

    /** @var string[] */
    private $capturedSegments = [];

    public function __construct(Method $method, Uri $uri)
    {
        $this->method = $method;
        $this->uri = $uri;

        $this->segments = array_map(function (string $segment): string {
            return self::isCapturedSegment($segment)
                ? self::CAPTURED_SEGMENT
                : $segment;
        }, explode(Uri::SEPARATOR, $method->getData() . $uri->getData()));

        $this->routeKey = implode(Uri::SEPARATOR, $this->segments);
    }

    public function getMethod(): Method
    {
        return $this->method;
    }

    public function getUri(): Uri
    {
        return $this->uri;
    }

    public function getSegments(): array
    {
        return $this->segments;
    }

    public function getRouteKey(): string
    {
        return $this->routeKey;
    }

    public function addCapturedSegment(string $capturedSegment): void
    {
        $this->capturedSegments[] = $capturedSegment;
    }

    public function getCapturedSegments(): array
    {
        return $this->capturedSegments;
    }

    private static function isCapturedSegment(string $segment): bool
    {
        return preg_match('#^{.+}$#', $segment) === 1;
    }
}
