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

class Paypal implements PaymentInterface
{
    public function startPayment(): void {
        var_dump(Di::getDefault()->getShared('paypal'));
        exit();
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
            // Call API with your client and get a response for your call
            $response = $this->paypal->execute($request);
            
            // If call returns body in response, you can get the deserialized version from the result attribute of the response
            print_r($response);
        } catch (\HttpException $e) {
            echo $e->statusCode;
            print_r($e->getMessage());
        }

        exit();
    }

    public function verifyPayment(): bool {

    }

    public function processCallback(): void {

    }

    public function isPaid(): bool {

    }
}
