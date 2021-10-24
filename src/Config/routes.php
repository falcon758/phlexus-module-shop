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

$routes->addPost('/cart/add/{id:[0-9]+}', [
    'controller' => 'shop',
    'action' => 'add',
]);

$routes->addPost('/cart/delete/{id:[0-9]+}', [
    'controller' => 'shop',
    'action' => 'remove',
]);

$routes->addGet('/checkout', [
    'controller' => 'shop',
    'action' => 'checkout',
]);

return $routes;
