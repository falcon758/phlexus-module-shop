<?php
declare(strict_types=1);

use Phalcon\Mvc\Router\Group as RouterGroup;

$routes = new RouterGroup([
    'module' => \Phlexus\Modules\Shop\Module::getModuleName(),
    'namespace' => \Phlexus\Modules\Shop\Module::getHandlersNamespace() . '\\Controllers',
    'controller' => 'cart',
    'action' => 'index',
]);

$routes->addGet('/cart', [
    'controller' => 'shop',
    'action' => 'cart',
]);

$routes->addGet('/products', [
    'controller' => 'shop',
    'action' => 'products',
]);

return $routes;
