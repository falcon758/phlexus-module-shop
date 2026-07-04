<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Tasks;

use Phalcon\Cli\Task;
use Phlexus\Modules\Shop\Models\Order;

class OrderTask extends Task
{
    public function closeExpiredAction()
    {
        $orders = Order::getAllInactiveOrders();

        foreach ($orders as $order) {
            if (!$order->expireOrder()) {
                error_log('Failed to close expired order id=' . $order->id, 0);
            }
        }
    }
}
