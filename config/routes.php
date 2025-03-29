<?php

use App\Controller\EsuLanguageController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->add('get_language', '/language')
        ->controller([EsuLanguageController::class, 'language'])
    ;
};