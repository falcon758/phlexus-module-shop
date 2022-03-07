<?php
declare(strict_types=1);

use Phalcon\Mvc\Router\Group as RouterGroup;

$routes = new RouterGroup([
    'module' => \Phlexus\Modules\Shop\Module::getModuleName(),
    'namespace' => \Phlexus\Modules\Shop\Module::getHandlersNamespace() . '\\Controllers',
    'controller' => 'cart',
    'action' => 'index',
]);

$routes->addGet('/shop/product', [
    'controller' => 'product',
    'action' => 'view',
]);

foreach (['create', 'view'] as $action) {
    $routes->addGet('/shop/product/' . $action, [
        'controller' => 'product',
        'action' => $action,
    ]);
}

$routes->addGet('/shop/product/edit/{id:[0-9]+}', [
    'controller' => 'product',
    'action' => 'edit',
]);

$routes->addPost('/shop/product/save', [
    'controller' => 'product',
    'action' => 'save',
]);

$routes->addPost('/shop/product/delete/{id:[0-9]+}', [
    'controller' => 'product',
    'action' => 'delete',
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

$routes->addPost('/checkout/order', [
    'controller' => 'shop',
    'action' => 'order',
]);

$routes->addGet('/order/success', [
    'controller' => 'shop',
    'action' => 'success',
]);

$routes->addGet('/order/cancel', [
    'controller' => 'shop',
    'action' => 'cancel',
]);

$routes->addGet('/payment/callback/paypal/{orderHash:[a-zA-Z0-9]+}', [
    'controller' => 'callback',
    'action' => 'paypal',
]);

return $routes;
