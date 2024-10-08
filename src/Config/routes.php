<?php
declare(strict_types=1);

use Phalcon\Mvc\Router\Group as RouterGroup;

$routes = new RouterGroup([
    'module'     => \Phlexus\Modules\Shop\Module::getModuleName(),
    'namespace'  => \Phlexus\Modules\Shop\Module::getHandlersNamespace() . '\\Controllers',
    'controller' => 'cart',
    'action'     => 'index',
]);

foreach (['product', 'product_attribute'] as $controller) {
    $routes->addGet('/shop/' . $controller, [
        'controller' => $controller,
        'action'     => 'view',
    ]);

    foreach (['create', 'view'] as $action) {
        $routes->addGet('/shop/' . $controller .'/' . $action, [
            'controller' => $controller,
            'action'     => $action,
        ]);
    }

    $routes->addGet('/shop/' . $controller . '/edit/{id:[0-9]+}', [
        'controller' => $controller,
        'action'     => 'edit',
    ]);

    $routes->addPost('/shop/' . $controller . '/save', [
        'controller' => $controller,
        'action'     => 'save',
    ]);

    $routes->addPost('/shop/' . $controller . '/delete/{id:[0-9]+}', [
        'controller' => $controller,
        'action'     => 'delete',
    ]);
}

$routes->addGet('/cart', [
    'controller' => 'shop',
    'action'     => 'cart',
]);

$routes->addGet('/products', [
    'controller' => 'shop',
    'action'     => 'products',
]);

$routes->addPost('/cart/add/{id:[0-9]+}', [
    'controller' => 'shop',
    'action'     => 'add',
]);

$routes->addPost('/cart/delete/{id:[0-9]+}', [
    'controller' => 'shop',
    'action'     => 'remove',
]);

$routes->addGet('/checkout', [
    'controller' => 'shop',
    'action'     => 'checkout',
]);

$routes->addPost('/checkout/order', [
    'controller' => 'shop',
    'action'     => 'order',
]);

$routes->addGet('/order/success', [
    'controller' => 'shop',
    'action'     => 'success',
]);

$routes->addGet('/order/cancel', [
    'controller' => 'shop',
    'action'     => 'cancel',
]);

$routes->addGet('/orders', [
    'controller' => 'order',
    'action'     => 'index',
]);

$routes->addGet('/order/{orderHash:[a-zA-Z0-9]+}', [
    'controller' => 'order',
    'action'     => 'view',
]);

$routes->addGet('/payments', [
    'controller' => 'payment',
    'action'     => 'index',
]);

$routes->addGet('/payments/history', [
    'controller' => 'payment',
    'action'     => 'history',
]);

$routes->addGet('/payment/{paymentHash:[a-zA-Z0-9]+}', [
    'controller' => 'payment',
    'action'     => 'view',
]);

$routes->addGet('/payment/pay/{paymentHash:[a-zA-Z0-9]+}', [
    'controller' => 'payment',
    'action'     => 'pay',
]);

$routes->addGet('/payment/callback/paypal/{paymentHash:[a-zA-Z0-9]+}', [
    'controller' => 'callback',
    'action'     => 'paypal',
]);

// @TODO: Remove after tests
$routes->addGet('/payment/callback/test/{paymentHash:[a-zA-Z0-9]+}', [
    'controller' => 'callback',
    'action'     => 'test',
]);

return $routes;
