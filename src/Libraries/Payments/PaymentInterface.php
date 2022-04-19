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

use Phalcon\Http\ResponseInterface;

interface PaymentInterface
{
    /**
     * Start payment process
     *
     * @return ResponseInterface
     */
    public function startPayment(): ResponseInterface;

    /**
     * Process a paymeny callback
     *
     * @param string $paymentID Order id
     * 
     * @return ResponseInterface
     */
    public function processCallback(string $paymentID): ResponseInterface;

    /**
     * Verify a payment
     *
     * @param string $paymentID Order id to verify
     * 
     * @return ResponseInterface
     */
    public function verifyPayment(string $paymentID): ResponseInterface;

    /**
     * Check if it's paid
     *
     * @param string $paymentID Order id to verify
     * 
     * @return bool
     */
    public function isPaid(string $paymentID): bool;
}
