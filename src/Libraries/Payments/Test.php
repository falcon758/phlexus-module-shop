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

class Test extends PaymentAbstract
{
    /**
     * Start payment process
     *
     * @return ResponseInterface
     */
    public function startPayment(): ResponseInterface {
        return $this->response->redirect('payment/callback/test/'. $this->payment->hashCode);
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
        $this->payment->paid();
        
        $translationMessage = Di::getDefault()->getShared('translation')->setTypeMessage();

        $this->flash->success($translationMessage->_('payment-processed-successfully'));

        $this->firePaymentSuccess();

        return $this->response->redirect('order/success');
    }

    /**
     * Check if it's paid
     *
     * @param string $paymentID Payment id
     * 
     * @return bool
     */
    public function isPaid(string $paymentID): bool {
        return true;
    }
}
