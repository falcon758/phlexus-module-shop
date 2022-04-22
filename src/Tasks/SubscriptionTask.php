<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Tasks;

use Phalcon\Cli\Task;
use Phlexus\Modules\Shop\Models\Order;

class SubscriptionTask extends Task
{
    public function createPaymentsAction()
    {
        $allOrders = Order::getAllRenewals();
        var_dump(count($allOrders));
    }

    public function verifySubscriptionAction()
    {
    }
}