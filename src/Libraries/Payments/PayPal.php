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
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;

class Paypal extends PaymentAbstract
{
    /**
     * Start payment process
     *
     * @return bool
     */
    public function startPayment(): bool {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [
                [
                    "reference_id" => "test_ref_id1",
                    "amount" => [
                        "value" => "100.00",
                        "currency_code" => "USD"
                    ]
                ]
            ],
            "application_context" => [
                "cancel_url" => "https://example.com/callback",
                "return_url" => "https://example.com/cart"
            ] 
        ];
        
        try {
            $paypal = Di::getDefault()->getShared('paypal');

            // Call API with your client and get a response for your call
            $response = $paypal->execute($request);
            
            if ($response->statusCode !== 201) {
                return false;
            }
            
            // If call returns body in response, you can get the deserialized version from the result attribute of the response
            print_r($response);
        } catch (\HttpException $e) {
            echo $e->statusCode;
            print_r($e->getMessage());
        }

        exit();
    }

    /**
     * Process a paymeny callback
     *
     * @return void
     */
    public function processCallback(): void {

    }

    /**
     * Verify a payment
     *
     * @return bool
     */
    public function verifyPayment(): bool {

    }

    /**
     * Check if it's paid
     *
     * @return bool
     */
    public function isPaid(): bool {

    }
}
