<?php

/*
|--------------------------------------------------------------------------
| Autoload php composer
|--------------------------------------------------------------------------
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| DI Conatiner
|--------------------------------------------------------------------------
*/

$container = new \Pimple\Container();

/*
|--------------------------------------------------------------------------
| Register dsefault services
|--------------------------------------------------------------------------
*/

$defaultSettings = [
    'httpVersion' => '1.1',
    'responseChunkSize' => 4096,
    'outputBuffering' => 'append',
    'determineRouteBeforeAppMiddleware' => false,
    'displayErrorDetails' => false,
    'addContentLengthHeader' => true,
    'routerCacheFile' => false,
];

if (!isset($container['settings'])) {
    $container['settings'] = function () use($defaultSettings) {
        return new \Slim\Psr7\Utilities\Collection($defaultSettings);
    };
}

if (!isset($container['environment'])) {
    $container['environment'] = function () {
        return new \Slim\Psr7\Http\Environment($_SERVER);
    };
}

if (!isset($container['request'])) {
    $container['request'] = function ($container) {
        return \Slim\Psr7\Http\Request::createFromEnvironment($container->offsetGet('environment'));
    };
}

if (!isset($container['response'])) {
    $container['response'] = function ($container) {
        $headers = new \Slim\Psr7\Http\Headers(['Content-Type' => 'text/html; charset=UTF-8']);
        $response = new \Slim\Psr7\Http\Response(200, $headers);

        return $response->withProtocolVersion($container->offsetGet('settings')['httpVersion']);
    };
}

if (!isset($container['router'])) {
    $container['router'] = function($container) {
        $router = new \Minion\Router();
        $router->setContainer($container);
        return $router;
    };
}

if (!isset($container['logger'])) {
    $container['logger'] = function($container) {
        $logger = new \Monolog\Logger('Debugger');
        $file_handler = new \Monolog\Handler\StreamHandler("debug.log");
        $logger->pushHandler($file_handler);
        return $logger;
    };
}

if (!isset($container['callableResolver'])) {
    /**
     * Instance of \Minion\Interfaces\CallableResolverInterface
     *
     * @param Container $container
     *
     * @return CallableResolverInterface
     */
    $container['callableResolver'] = function ($container) {
        return new \Minion\CallableResolver($container);
    };
}

/*
|--------------------------------------------------------------------------
| Application
|--------------------------------------------------------------------------
*/

$app = new \Minion\Application($container);

/*
|--------------------------------------------------------------------------
| Dispatch routes
|--------------------------------------------------------------------------
*/

$app->route('GET', '/', function($request, $response, $args) {
    print "Hello world";
});

// Ignite

$app->run();

// $container = $app->getContainer();

// $container['config'] = false;

// bool :: $container->has('config')
// var_dump($container->offsetExists('config'));
// mixed :: $container->get('config')
// var_dump($container->offsetExists('config'));

// $dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $router) {
//     $router->addRoute('GET', '/', function() {
//         print "Hello world";
//     });
// });

// // Fetch method and URI from somewhere
// $httpMethod = $_SERVER['REQUEST_METHOD'];
// $uri = $_SERVER['REQUEST_URI'];
// // Strip query string (?foo=bar) and decode URI
// if (false !== $pos = strpos($uri, '?')) {
//     $uri = substr($uri, 0, $pos);
// }
// $uri = rawurldecode($uri);
// $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
// switch ($routeInfo[0]) {
//     case FastRoute\Dispatcher::NOT_FOUND:
//         throw new \Exception("404 - Not Found");
//         break;
//     case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
//         $allowedMethods = $routeInfo[1];
//         throw new \Exception("405 - Method Not Allowed");
//         break;
//     case FastRoute\Dispatcher::FOUND:
//         $handler = $routeInfo[1];
//         $reflection = new \ReflectionFunction($handler);
//         if($reflection->isClosure()) {
//             $reflection->invoke();
//         }
//         break;
// }
