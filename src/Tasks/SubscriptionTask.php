<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Tasks;

use Phalcon\Cli\Task;
use Phlexus\Modules\Shop\Models\Order;
use Phlexus\Modules\Shop\Models\Item;
use Phlexus\Modules\Shop\Models\Payment;
use Phlexus\Modules\Shop\Models\PaymentType;

class SubscriptionTask extends Task
{
    public function createPaymentsAction()
    {
        $allOrders = Order::getAllRenewals();

        foreach ($allOrders as $order) {
            try {
                if (((int) $order->itemsCount) === 1) {
                    Payment::createPayment(
                        (float) $order->totalPrice,
                        PaymentType::RENEWAL,
                        (int) $order->paymentMethodID,
                        (int) $order->orderID
                    );
                } else {
                    if (!Item::disableOrderItem((int) $order->itemID, (int) $order->orderID)) {
                        error_log('Failed to disable item!', 0);
                        continue;
                    }

                    $newOrder = Order::renewalOrder(
                        (int) $order->userID,
                        (int) $order->billingID,
                        (int) $order->shipmentID,
                        (int) $order->paymentMethodID,
                        (int) $order->shippingMethodID,
                        (int) $order->orderID
                    );

                    if (!$newOrder) {
                        error_log('Failed to create new order!', 0);
                        continue;
                    }

                    $item = Item::createItems((int) $order->id, [$order->productID => $order->quantity]);

                    if (!$item) {
                        error_log('Failed to create new item!', 0);
                        continue;
                    }

                    Payment::createPayment(
                        (float) $order->totalPrice,
                        PaymentType::RENEWAL,
                        (int) $newOrder->paymentMethodID,
                        (int) $newOrder->id
                    );
                }
            } catch(\Exception $e) {
                error_log('Failed to create renewal: ' . $e->getMessage(), 0);
            }
        }
    }

    public function verifyPaymentsAction()
    {
        // Verify payments in case something went wrong
    }

    public function verifySubscriptionAction()
    {
        $allExpired = Order::getAllExpired();

        foreach ($allExpired as $expired) {
            if (!$expired->disableItem()) {
                error_log('Failed to disable item!', 0);
            }
            
            // Also cancel payments
        }
    }
}