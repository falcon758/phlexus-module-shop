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
use Phlexus\Modules\Shop\Models\Payment;
use Phlexus\Modules\Shop\Models\PaymentMethod;

class PaymentFactory
{
    /**
     * Build Payments
     * 
     * @return PaymentInterface
     */
    public function build(Payment $payment): PaymentInterface {
        switch ($payment->paymentMethodID) {
            // @TODO: Remove after test
            case PaymentMethod::TEST:
                return new Test($payment);
            case PaymentMethod::PAYPAL:
            default:
                return new PayPal($payment);
        }
    }
}