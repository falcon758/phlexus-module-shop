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

use Phlexus\Helpers;
use Phlexus\Modules\Shop\Models\Order;
use Phlexus\Modules\Shop\Models\PaymentMethod;

class PaymentFactory
{
    /**
     * Build Payments
     * 
     * @return Order
     */
    public function build(Order $order): PaymentInterface {
        switch ($order->paymentMethodID) {
            case PaymentMethod::PAYPAL:
            default:
                return new PayPal($order);
                break;
        }
    }
}