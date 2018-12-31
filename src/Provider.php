<?php

namespace App;

use Pimple\Container;

class Provider {

    /**
     * Register services.
     * @param  \Pimple\Container $container
     * @return void
     */
    public function register(Container $container)
    {
        /**
         * Inject twig templating.
         */
        if (!isset($container['twig'])) {
            $container['twig'] = function () {
                // Specify our Twig templates location
                $loader = new \Twig_Loader_Filesystem(__DIR__.'/../views');

                // Instantiate our Twig
                return new \Twig_Environment($loader, [
                    'cache' => __DIR__.'/../views/cache',
                    // Recompile the template whenever the source code changes
                    // https://twig.symfony.com/doc/2.x/api.html#environment-options
                    'auto_reload' => true,
                ]);

                // return new \Twig_Environment($loader, [
                //     'auto_reload' => true
                // ]);
            };
        }
    }

}
