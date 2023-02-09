<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phalcon\Tag;
use Phalcon\Http\ResponseInterface;
use Phlexus\Modules\Shop\Models\Payment;
use Phlexus\Modules\Shop\Libraries\Payments\PayPal;

/**
 * @RoutePrefix('/payment/callback')
 */
class CallbackController extends AbstractController
{
    /**
     * @Get('/payment/callback/paypal')
     */
    public function paypalAction(string $paymentHash): ResponseInterface
    {
        $title = $this->translation->setTypePage()->_('title-shop-callback-paypal');

        Tag::setTitle($title);

        $payment = Payment::findFirstByhashCode($paymentHash);

        if (!$payment) {
            return $this->response->redirect('checkout');
        }

        $token = (string) $this->request->get('token');

        if (preg_match('/^[a-zA-Z0-9]+$/', $token) !== 1) {
            return $this->response->redirect('checkout');
        }

        return (new Paypal($payment))->processCallback($token);
    }
}
