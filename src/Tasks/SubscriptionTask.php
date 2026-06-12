<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Tasks;

use Phalcon\Cli\Task;
use Phlexus\Modules\Shop\Models\Order;
use Phlexus\Modules\Shop\Models\Item;
use Phlexus\Modules\Shop\Models\Payment;
use Phlexus\Modules\Shop\Models\PaymentStatus;
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
        // Cancel any active CREATED renewal payments whose item has already
        // been disabled (e.g. by a prior verifySubscription run or manual action).
        $p_model = Payment::class;
        $i_model = Item::class;

        $orphaned = Payment::query()
            ->innerJoin(Order::class, "$p_model.orderID = ORP.id", 'ORP')
            ->innerJoin($i_model, "ORP.id = IRP.orderID AND IRP.active = " . Item::DISABLED, 'IRP')
            ->where(
                "$p_model.active = :active:
                AND $p_model.statusID = :statusID:
                AND $p_model.paymentTypeID = :paymentTypeID:",
                [
                    'active'        => Payment::ENABLED,
                    'statusID'      => PaymentStatus::CREATED,
                    'paymentTypeID' => PaymentType::RENEWAL,
                ]
            )
            ->execute();

        foreach ($orphaned as $payment) {
            if (!$payment->cancelPayment()) {
                error_log('Failed to cancel orphaned payment id=' . $payment->id, 0);
            }
        }
    }

    public function verifySubscriptionAction()
    {
        $allExpired = Order::getAllExpired();

        foreach ($allExpired as $expired) {
            if (!$expired->disableItem()) {
                error_log('Failed to disable item!', 0);
            }

            // Cancel all pending CREATED renewal payments for this order so
            // they cannot be paid after the subscription has expired.
            $pendingPayments = Payment::find([
                'conditions' => 'orderID = :orderID:
                    AND statusID = :statusID:
                    AND paymentTypeID = :paymentTypeID:
                    AND active = :active:',
                'bind' => [
                    'orderID'       => (int) $expired->orderID,
                    'statusID'      => PaymentStatus::CREATED,
                    'paymentTypeID' => PaymentType::RENEWAL,
                    'active'        => Payment::ENABLED,
                ],
            ]);

            foreach ($pendingPayments as $payment) {
                if (!$payment->cancelPayment()) {
                    error_log('Failed to cancel payment id=' . $payment->id . ' for expired order id=' . $expired->orderID, 0);
                }
            }
        }
    }
}