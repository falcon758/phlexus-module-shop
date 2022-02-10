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

use Phlexus\Modules\Shop\Models\Order;

abstract class PaymentAbstract implements PaymentInterface
{
    /**
     * Order
     * 
     * @var Order
     */
    protected Order $order;

    /**
     * Construct Payment
     * 
     * @param string $order Order to process
     */
    public function __construct(Order $order) {
        $this->order = $order;
    }
}