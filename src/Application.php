<?php

namespace App;

use Pimple\Container;

class Application {

    private $container;

    public function __construct()
    {
        $this->container = new Container();
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function registerServices()
    {
        $provider = new Provider();
        $provider->register($this->container);
    }

    public function disptachRoutes()
    {
        $router = new Router();
        $router->disptach($this->container);
    }

}
