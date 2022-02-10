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
    public function startPayment(): void;

    public function verifyPayment(): bool;

    public function processCallback(): void;

    public function isPaid(): bool;
}
