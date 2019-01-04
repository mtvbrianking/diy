<?php

namespace Minion;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\RouteParser\Std as StdParser;
use Pimple\Container;
use Psr\Http\Message\ServerRequestInterface;

class Router {

    protected $basePath = '';

    protected $container;

    /**
     * Routes
     *
     * @var Route[]
     */
    protected $routes = [];

    /**
     * Route count incrementer
     * @var int
     */
    protected $routeCount = 0;

    /**
     * Parser
     *
     * @var \FastRoute\RouteParser
     */
    protected $routeParser;

    /**
     * @var \FastRoute\Dispatcher
     */
    protected $dispatcher;

    public function __construct(RouteParser $parser = null)
    {
        $this->routeParser = $parser ?: new StdParser;
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function setBasePath($basePath)
    {
        if (!is_string($basePath)) {
            throw new InvalidArgumentException('Router basePath must be a string');
        }

        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Get route objects
     *
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    public function route($method, $pattern, $callable)
    {
        if (!is_string($pattern)) {
            throw new InvalidArgumentException('Route pattern must be a string');
        }

        // According to RFC methods are defined in uppercase (See RFC 7231)
        $method = strtoupper($method);

        $route = new Route($method, $pattern, $callable, $this->routeCount);
        if (!empty($this->container)) {
            $route->setContainer($this->container);
        }

        // Keep track of routes...
        $this->routes[$route->getIdentifier()] = $route;
        $this->routeCount++;

        return $route;
    }

    /**
     * @return \FastRoute\Dispatcher
     */
    protected function createDispatcher()
    {
        if ($this->dispatcher) {
            return $this->dispatcher;
        }

        $routeDefinitionCallback = function (RouteCollector $r) {
            foreach ($this->getRoutes() as $route) {
                $r->addRoute($route->getMethod(), $route->getPattern(), $route->getIdentifier());
            }
        };

        $this->dispatcher = \FastRoute\simpleDispatcher($routeDefinitionCallback, [
            'routeParser' => $this->routeParser,
        ]);

        return $this->dispatcher;
    }

    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function dispatch(ServerRequestInterface $request)
    {
        $uri = '/' . ltrim($request->getUri()->getPath(), '/');

        return $this->createDispatcher()->dispatch(
            $request->getMethod(),
            $uri
        );
    }

    public function lookupRoute($identifier)
    {
        if (!isset($this->routes[$identifier])) {
            throw new RuntimeException('Route not found, looks like your route cache is stale.');
        }
        return $this->routes[$identifier];
    }

}
