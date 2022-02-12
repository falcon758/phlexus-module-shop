<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Controller;
use Phlexus\Modules\Shop\Models\Order;
use Phlexus\Modules\Shop\Libraries\Payments\PayPal;

/**
 * @RoutePrefix('/payment')
 *
 */
class PaymentController extends Controller
{
    /**
     * @Get('/')
     */
    public function paypalAction(string $orderHash): ResponseInterface
    {
        $order = Order::findFirstByhashCode($orderHash);

        if (!$order) {
            return $this->response->redirect('checkout');
        }

        return (new Paypal($order))->processCallback();
    }
}
