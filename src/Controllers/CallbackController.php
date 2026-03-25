<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phalcon\Tag;
use Phalcon\Http\ResponseInterface;
use Phlexus\Modules\Shop\Models\Payment;
use Phlexus\Modules\Shop\Libraries\Payments\Stripe;
use Phlexus\Modules\Shop\Libraries\Payments\Test;

/**
 * @RoutePrefix('/payment/callback')
 */
class CallbackController extends AbstractController
{
    /**
     * @Get('/payment/callback/apple')
     */
    public function stripeAction(string $paymentHash): ResponseInterface
    {
        $title = $this->translation->setTypePage()->_('title-shop-callback-stripe');

        Tag::setTitle($title);

        $payment = Payment::findFirstByhashCode($paymentHash);

        if (!$payment) {
            return $this->response->redirect('checkout');
        }

        $sessionID = (string) $this->request->get('session_id');

        if (preg_match('/^[a-zA-Z0-9_]+$/', $sessionID) !== 1) {
            return $this->response->redirect('checkout');
        }

        return (new Stripe($payment))->processCallback($sessionID);
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
