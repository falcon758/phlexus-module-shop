<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phalcon\Tag;
use Phalcon\Http\ResponseInterface;
use Phlexus\Modules\Shop\Models\Payment;
use Phlexus\Modules\Shop\Libraries\Payments\PayPal;
use Phlexus\Modules\Shop\Libraries\Payments\Test;
use Phlexus\Modules\Shop\Libraries\Payments\ApplePay;
use Phlexus\Modules\Shop\Libraries\Payments\GooglePay;

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

    /**
     * @Get('/payment/callback/apple')
     */
    public function appleAction(string $paymentHash): ResponseInterface
    {
        $title = $this->translation->setTypePage()->_('title-shop-callback-apple');

        Tag::setTitle($title);

        $payment = Payment::findFirstByhashCode($paymentHash);

        if (!$payment) {
            return $this->response->redirect('checkout');
        }

        $sessionID = (string) $this->request->get('session_id');

        if (preg_match('/^[a-zA-Z0-9_]+$/', $sessionID) !== 1) {
            return $this->response->redirect('checkout');
        }

        return (new ApplePay($payment))->processCallback($sessionID);
    }

    /**
     * @Get('/payment/callback/google')
     */
    public function googleAction(string $paymentHash): ResponseInterface
    {
        $title = $this->translation->setTypePage()->_('title-shop-callback-google');

        Tag::setTitle($title);

        $payment = Payment::findFirstByhashCode($paymentHash);

        if (!$payment) {
            return $this->response->redirect('checkout');
        }

        $sessionID = (string) $this->request->get('session_id');

        if (preg_match('/^[a-zA-Z0-9_]+$/', $sessionID) !== 1) {
            return $this->response->redirect('checkout');
        }

        return (new GooglePay($payment))->processCallback($sessionID);
    }

    /**
     * @Get('/payment/callback/test')
     */
    public function testAction(string $paymentHash): ResponseInterface
    {
        $title = $this->translation->setTypePage()->_('title-shop-callback-test');

        Tag::setTitle($title);

        $payment = Payment::findFirstByhashCode($paymentHash);

        if (!$payment) {
            return $this->response->redirect('checkout');
        }

        return (new Test($payment))->processCallback("");
    }
}
