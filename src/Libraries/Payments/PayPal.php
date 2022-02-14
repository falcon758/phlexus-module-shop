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
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;

class Paypal extends PaymentAbstract
{
    private const PAYPALORDER = 'paypal_order_id';

    /**
     * Start payment process
     *
     * @return ResponseInterface
     */
    public function startPayment(): ResponseInterface {
        $items = $this->order->getItems();

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
                'return_url' => $this->url->get('/payment/callback/paypal/'. $this->order->hashCode),
                'cancel_url' => $this->url->get('/checkout'),
            ] 
        ];

        try {
            $response = Di::getDefault()->getShared('paypal')->execute($request);
            
            if ($response->statusCode !== 201) {
                return $this->response->redirect('checkout');
            }

            foreach ($response->result->links as $link) {
                if ($link->rel === 'approve' && $this->order->setAttributes([
                    self::PAYPALORDER => $response->result->id
                ])) {
                    return $this->response->redirect($link->href);
                }
            }
        } catch (\HttpException $e) {
            $this->flash->error('Unable to process payment!');

            return $this->response->redirect('checkout');
        }

        $this->flash->error('Unable to process payment!');

        return $this->response->redirect('checkout');
    }

    /**
     * Process a paymeny callback
     *
     * @param string $orderID Order id
     * 
     * @return ResponseInterface
     */
    public function processCallback(string $orderID): ResponseInterface {
        return $this->verifyPayment($orderID);
    }

    /**
     * Verify a payment
     *
     * @param string $orderID Order id to verify
     * 
     * @return ResponseInterface
     */
    public function verifyPayment(string $orderID): ResponseInterface {
        if ($this->order->isPaid()) {
            $this->flash->warning('Order already paid!');

            return $this->response->redirect('products');
        } else if ($this->isPaid($orderID)) {
            $this->order->paidOrder();

            return $this->response->redirect('order/success');
        }

        $this->flash->error('Unable to process payment!');

        return $this->response->redirect('checkout');
    }

    /**
     * Check if it's paid
     *
     * @param string $orderID Order id to verify
     * 
     * @return bool
     */
    public function isPaid(string $orderID): bool {
        $request = new OrdersCaptureRequest($orderID);
        $request->prefer('return=representation');
        try {
            $response = Di::getDefault()->getShared('paypal')->execute($request);

            if ($response->statusCode === 201 && $response->result->status === 'COMPLETED') {
                return true;
            }
        } catch (HttpException $e) {
            return false;
        }

        return false;
    }
}
