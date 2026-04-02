<?php

/**
 * This file is part of the Phlexus CMS.
 *
 * (c) Phlexus CMS <cms@phlexus.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Libraries\Payments;

use Phlexus\Modules\Shop\Models\PaymentAttribute;
use Phalcon\Di\Di;
use Phalcon\Http\ResponseInterface;

class Stripe extends PaymentAbstract
{
    private const STRIPE_SESSION = 'stripe_session_id';

    /**
     * Start payment process
     *
     * @return ResponseInterface
     */
    public function startPayment(): ResponseInterface {
        $payment = $this->payment;
        $order   = $payment->order;

        $items = $order->getItems();

        $lineItems = [];
        foreach ($items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'EUR',
                    'product_data' => [
                        'name' => $item['productName'],
                    ],
                    'unit_amount'  => (int) round(((float) $item['price']) * 100),
                ],
                'quantity' => (int) $item['quantity'],
            ];
        }

        $translationMessage = Di::getDefault()->getShared('translation')->setTypeMessage();

        try {
            $stripe = Di::getDefault()->getShared('stripe');

            $session = $stripe->checkout->sessions->create([
                'mode'        => 'payment',
                'line_items'  => $lineItems,
                'success_url' => $this->url->get('/payment/callback/stripe/' . $payment->hashCode) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => $this->url->get('/checkout'),
            ]);

            if (
                isset($session->id, $session->url)
                && PaymentAttribute::setAttributes($payment->id, [self::STRIPE_SESSION => $session->id])
            ) {
                return $this->response->redirect($session->url);
            }
        } catch (\Exception $e) {
            $this->flash->error($translationMessage->_('unable-to-process-payment'));

            return $this->response->redirect('checkout');
        }

        $this->flash->error($translationMessage->_('unable-to-process-payment'));

        return $this->response->redirect('checkout');
    }

    /**
     * Process a payment callback
     *
     * @param string $paymentID Stripe Checkout session id
     *
     * @return ResponseInterface
     */
    public function processCallback(string $paymentID): ResponseInterface {
        return $this->verifyPayment($paymentID);
    }

    /**
     * Verify a payment
     *
     * @param string $paymentID Stripe Checkout session id
     *
     * @return ResponseInterface
     */
    public function verifyPayment(string $paymentID): ResponseInterface {
        $payment = $this->payment;

        $attributes = PaymentAttribute::getAttributes($payment->id, [self::STRIPE_SESSION]);
        if (count($attributes) === 0 || $attributes[0]['value'] !== $paymentID) {
            return $this->response->redirect('products');
        }

        $translationMessage = Di::getDefault()->getShared('translation')->setTypeMessage();

        if ($payment->isPaid()) {
            $this->flash->warning($translationMessage->_('order-already-paid'));

            return $this->response->redirect('products');
        } else if ($this->isPaid($paymentID)) {
            $payment->paid();

            $this->flash->success($translationMessage->_('payment-processed-successfully'));

            $this->firePaymentSuccess();

            return $this->response->redirect('order/success');
        }

        $this->flash->error($translationMessage->_('unable-to-process-payment'));

        return $this->response->redirect('checkout');
    }

    /**
     * Check if it's paid
     *
     * @param string $paymentID Stripe Checkout session id
     *
     * @return bool
     */
    public function isPaid(string $paymentID): bool {
        try {
            $stripe = Di::getDefault()->getShared('stripe');
            $session = $stripe->checkout->sessions->retrieve($paymentID, []);

            if (isset($session->payment_status) && $session->payment_status === 'paid') {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
