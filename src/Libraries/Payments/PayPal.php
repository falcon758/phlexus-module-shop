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
                'return_url' => $this->url->get('/payment/paypal/'. $this->order->hashCode),
                'cancel_url' => $this->url->get('/checkout'),
            ] 
        ];

        try {
            $response = Di::getDefault()->getShared('paypal')->execute($request);
            
            if ($response->statusCode !== 201) {
                return $this->response->redirect('checkout');
            }

            foreach ($response->result->links as $link) {
                if ($link->rel === 'approve') {
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
     * @return ResponseInterface
     */
    public function processCallback(): ResponseInterface {
        return $this->verifyPayment();
    }

    /**
     * Verify a payment
     *
     * @return ResponseInterface
     */
    public function verifyPayment(): ResponseInterface {
        $request = new OrdersCaptureRequest("APPROVED-ORDER-ID");
        $request->prefer('return=representation');
        try {
            $response = $client->execute($request);
            
            print_r($response);
            exit();
        } catch (HttpException $e) {
            return $this->response->redirect('checkout');
        }
    }

    /**
     * Check if it's paid
     *
     * @return bool
     */
    public function isPaid(): bool {

    }
}
