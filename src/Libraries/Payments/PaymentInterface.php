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
     * @return ResponseInterface
     */
    public function processCallback(): ResponseInterface;

    /**
     * Verify a payment
     *
     * @return ResponseInterface
     */
    public function verifyPayment(): ResponseInterface;

    /**
     * Check if it's paid
     *
     * @return bool
     */
    public function isPaid(): bool;
}
