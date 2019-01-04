<?php

namespace Minion;

use Closure;
use FastRoute\Dispatcher;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;

class Application {

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function __call($method, $args)
    {
        if ($this->container->offsetExists($method)) {
            $obj = $this->container[$method];
            if (is_callable($obj)) {
                return call_user_func_array($obj, $args);
            }
        }

        throw new \Exception("Method $method is not a valid method");
    }

    public function route($methods, $pattern, $callable)
    {
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this->container);
        }

        $route = $this->container['router']->route($methods, $pattern, $callable);

        $route->setContainer($this->container);

        return $route;
    }

    public function run()
    {
        $request = $this->container->offsetGet('request');

        $response = $this->container->offsetGet('response');

        $router = $this->container->offsetGet('router');

        // echo "<pre>";
        // var_dump($request);

        if (is_callable([$request->getUri(), 'getBasePath']) && is_callable([$router, 'setBasePath'])) {
            $router->setBasePath($request->getUri()->getBasePath());
        }

        $routeInfo = $router->dispatch($request);

        if ($routeInfo[0] === Dispatcher::FOUND) {
            $routeArguments = [];
            foreach ($routeInfo[2] as $key => $value) {
                $routeArguments[$key] = urldecode($value);
            }

            $route = $router->lookupRoute($routeInfo[1]);
            $route->prepare($request, $routeArguments);

            // add route to the request's attributes in case a middleware or handler needs access to the route
            $request = $request->withAttribute('route', $route);
        }

        $routeInfo['request'] = [$request->getMethod(), (string) $request->getUri()];

        // return $request->withAttribute('routeInfo', $routeInfo);

        $request->withAttribute('routeInfo', $routeInfo);

        // ..........................................................
        // Invoke Route Ojbect
        $route($request, $response);
        // ..........................................................

        $this->respond($response);

        return $response;
    }

    public function respond(ResponseInterface $response)
    {
        // Send response
        if (!headers_sent()) {
            // Headers
            foreach ($response->getHeaders() as $name => $values) {
                $first = stripos($name, 'Set-Cookie') === 0 ? false : true;
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), $first);
                    $first = false;
                }
            }

            // Set the status _after_ the headers, because of PHP's "helpful" behavior with location headers.
            // See https://github.com/slimphp/Slim/issues/1730

            // Status
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ), true, $response->getStatusCode());
        }

        // Body
        if (!$this->isEmptyResponse($response)) {
            $body = $response->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $settings       = $this->container->offsetGet('settings');
            $chunkSize      = $settings['responseChunkSize'];

            $contentLength  = $response->getHeaderLine('Content-Length');
            if (!$contentLength) {
                $contentLength = $body->getSize();
            }


            if (isset($contentLength)) {
                $amountToRead = $contentLength;
                while ($amountToRead > 0 && !$body->eof()) {
                    $data = $body->read(min($chunkSize, $amountToRead));
                    echo $data;

                    $amountToRead -= strlen($data);

                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }
    }

    protected function isEmptyResponse(ResponseInterface $response)
    {
        if (method_exists($response, 'isEmpty')) {
            return $response->isEmpty();
        }

        return in_array($response->getStatusCode(), [204, 205, 304]);
    }

}
