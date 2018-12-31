<?php

namespace App;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Pimple\Container;

class Router {

    /**
     * Register routes.
     * @return \FastRoute\simpleDispatcher $dispatcher
     */
    public function register()
    {
        return \FastRoute\simpleDispatcher(function(RouteCollector $collection) {
            $collection->addRoute('GET', '/', 'App\Controllers\Controller@index');
        });
    }

    /**
     * Dispatch http requests.
     * @param  \Pimple\Container $container
     * @return void
     */
    public function disptach(Container $container)
    {
        // Register routes...
        $dispatcher = $this->register();

        // Fetch method and URI from somewhere
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new \Exception("404 - Not Found");
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                throw new \Exception("405 - Method Not Allowed");
                break;
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];

                if(is_string($handler)) {
                    // https://catchmetech.com/en/post/91/how-to-invoke-method-on-class-with-dynamicall-generated-name-via-reflection
                    list($class, $method) = explode("@", $handler, 2);

                    // Add controller namespace
                    if (strpos($class, "\\") === false) {
                        $class = "App\\Controllers\\".$class;
                    }

                    $args = [
                        $container,
                    ];

                    $reflectionClass  = new \ReflectionClass($class);
                    $instance = $reflectionClass->newInstanceArgs($args);
                    $reflectionMethod = $reflectionClass->getMethod($method);
                    $reflectionMethod->invokeArgs($instance, $vars);
                } else {
                    $reflection = new \ReflectionFunction($handler);
                    if($reflection->isClosure()) {
                        // https://stackoverflow.com/a/17373577/2732184
                        $reflection->invoke();
                    }
                }
                break;
        }
    }

}
