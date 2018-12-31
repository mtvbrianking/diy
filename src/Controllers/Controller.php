<?php

namespace App\Controllers;

use Pimple\Container;

class Controller {

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function index()
    {
        header("HTTP/1.1 200 OK");
        header("Content-type: text/html; charset=UTF-8");
        http_response_code(200);
        print $this->container['twig']->render("index.twig", [
            "name" => "Brian",
        ]);
    }

}
