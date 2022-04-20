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

use Phalcon\Di;
use Phalcon\Http\ResponseInterface;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;

class Paypal extends PaymentAbstract
{
    private const PAYPALORDER = 'paypal_order_id';

    /**
     * Start payment process
     *
     * @return ResponseInterface
     */
    public function startPayment(): ResponseInterface {
        $payment = $this->payment;
        $order   = $payment->order;

        $items = $order->getItems();

        $arrProd = [];

        foreach ($items as $item) {
            $arrProd[] = [
                'reference_id'    => $item['id'],
                'description'     => $item['name'],
                'amount' => [
                    'value'         => $item['price'],
                    'currency_code' => 'EUR'
                ]
            ];
        }

        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            'intent'              => 'CAPTURE',
            'purchase_units'      => $arrProd,
            'application_context' => [
                'return_url' => $this->url->get('/payment/callback/paypal/'. $payment->hashCode),
                'cancel_url' => $this->url->get('/checkout'),
            ] 
        ];

        $di = Di::getDefault();

        $translationMessage = $di->getShared('translation')->setTypeMessage();

        try {
            $response = $di->getShared('paypal')->execute($request);
            
            if ($response->statusCode !== 201) {
                return $this->response->redirect('checkout');
            }

            foreach ($response->result->links as $link) {
                if ($link->rel === 'approve' && $payment->setAttributes([
                    self::PAYPALORDER => $response->result->id
                ])) {
                    return $this->response->redirect($link->href);
                }
            }
        } catch (\HttpException $e) {
            $this->flash->error($translationMessage->_('unable-to-process-payment'));
    
            return $this->response->redirect('checkout');
        }

        $this->flash->error($translationMessage->_('unable-to-process-payment'));

        return $this->response->redirect('checkout');
    }

    /**
     * Process a paymeny callback
     *
     * @param string $paymentID Payment id
     * 
     * @return ResponseInterface
     */
    public function processCallback(string $paymentID): ResponseInterface {
        return $this->verifyPayment($paymentID);
    }

    /**
     * Verify a payment
     *
     * @param string $paymentID Payment id
     * 
     * @return ResponseInterface
     */
    public function verifyPayment(string $paymentID): ResponseInterface {
        $payment = $this->payment;
        $order   = $payment->order;

        $attribute = $payment->getAttributes([self::PAYPALORDER]);

        if (count($attribute) === 0 || $attribute[0]['value'] !== $paymentID) {
            return $this->response->redirect('products');
        }
        
        $translationMessage = Di::getDefault()->getShared('translation')->setTypeMessage();

        if ($payment->isPaid()) {
            $this->flash->warning($translationMessage->_('order-already-paid'));

            return $this->response->redirect('products');
        } else if ($this->isPaid($paymentID)) {
            $payment->paid();

            $this->flash->success($translationMessage->_('payment-processed-successfully'));

            return $this->response->redirect('order/success');
        }

        $this->flash->error($translationMessage->_('unable-to-process-payment'));

        return $this->response->redirect('checkout');
    }

    /**
     * Check if it's paid
     *
     * @param string $paymentID Payment id
     * 
     * @return bool
     */
    public function isPaid(string $paymentID): bool {
        $request = new OrdersGetRequest($paymentID);

        try {
            $response = Di::getDefault()->getShared('paypal')->execute($request);

            if ($response->statusCode === 200 && in_array($response->result->status, ['APPROVED', 'COMPLETED'])) {
                return true;
            }
        } catch (HttpException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}
