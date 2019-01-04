<?php

namespace Minion;

use Minion\CallableResolverAwareTrait;
use Pimple\Container;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Route {

    protected $callable;

    protected $container;

    protected $identifier;

    protected $method;

    protected $pattern;

    /**
     * Route parameters
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Route arguments parameters
     *
     * @var null|array
     */
    protected $savedArguments = [];

    public function setContainer(Container $container)
    {
        $this->container = $container;
        return $this;
    }

    public function __construct($method, $pattern, $callable, $identifier = 0)
    {
        $this->method  = $method;
        $this->pattern  = $pattern;
        $this->callable = $callable;
        $this->identifier = 'route' . $identifier;
    }

    public function getCallable()
    {
        return $this->callable;
    }

    public function setCallable($callable)
    {
        $this->callable = $callable;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set a route argument
     *
     * @param string $name
     * @param string $value
     *
     * @return self
     */
    public function setArgument($name, $value)
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * Retrieve a specific route argument
     *
     * @param string $name
     * @param string|null $default
     *
     * @return mixed
     */
    public function getArgument($name, $default = null)
    {
        if (array_key_exists($name, $this->arguments)) {
            return $this->arguments[$name];
        }
        return $default;
    }

    /**
     * Replace route arguments
     *
     * @param array $arguments
     *
     * @return self
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Retrieve route arguments
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Get the route pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    protected function resolveCallable($callable)
    {
        if (!$this->container instanceof Container) {
            return $callable;
        }

        /** @var CallableResolverInterface $resolver */
        $resolver = $this->container->offsetGet('callableResolver');

        return $resolver->resolve($callable);
    }

    public function prepare(ServerRequestInterface $request, array $arguments)
    {
        // Add the route arguments
        foreach ($arguments as $key => $value) {
            $this->setArgument($key, $value, false);
        }
    }

    // From: \Slim\Handlers\Strategies\RequestResponse => hanler()
    public function handler(callable $callable, ServerRequestInterface $request, ResponseInterface $response, array $routeArguments) {
        foreach ($routeArguments as $k => $v) {
            $request = $request->withAttribute($k, $v);
        }

        return call_user_func($callable, $request, $response, $routeArguments);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->callable = $this->resolveCallable($this->callable);

        /** @var InvocationStrategyInterface $handler */
        // $handler = isset($this->container) ? $this->container->offsetGet('foundHandler') : new RequestResponse();
        // $newResponse = $handler($this->callable, $request, $response, $this->arguments);

        $newResponse = $this->handler($this->callable, $request, $response, $this->arguments);

        if ($newResponse instanceof ResponseInterface) {
            // if route callback returns a ResponseInterface, then use it
            $response = $newResponse;
        } elseif (is_string($newResponse)) {
            // if route callback returns a string, then append it to the response
            if ($response->getBody()->isWritable()) {
                $response->getBody()->write($newResponse);
            }
        }

        return $response;
    }

}
