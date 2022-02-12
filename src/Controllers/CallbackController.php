<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Controller;
use Phlexus\Modules\Shop\Models\Order;
use Phlexus\Modules\Shop\Libraries\Payments\PayPal;

/**
 * @RoutePrefix('/payment/callback')
 *
 */
class CallbackController extends Controller
{
    /**
     * @Get('/payment/callback/paypal')
     */
    public function paypalAction(string $orderHash): ResponseInterface
    {
        $order = Order::findFirstByhashCode($orderHash);

        if (!$order) {
            return $this->response->redirect('checkout');
        }

        $token = $this->request->get('token');

        if (preg_match('/^[a-zA-Z0-9]+$/', $token) !== 1) {
            return $this->response->redirect('checkout');
        }

        return (new Paypal($order))->processCallback($token);
    }
}
