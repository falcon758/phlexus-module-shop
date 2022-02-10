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

interface PaymentInterface
{
    /**
     * Start payment process
     *
     * @return bool
     */
    public function startPayment(): bool;

    /**
     * Process a paymeny callback
     *
     * @return void
     */
    public function processCallback(): void;

    /**
     * Verify a payment
     *
     * @return bool
     */
    public function verifyPayment(): bool;

    /**
     * Check if it's paid
     *
     * @return bool
     */
    public function isPaid(): bool;
}
