<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Tasks;

use Phalcon\Cli\Task;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;
use Phlexus\Helpers;
use Phlexus\Modules\BaseUser\Models\User;
use Phlexus\Modules\Shop\Models\Order;
use Phlexus\Modules\Shop\Models\Item;
use Phlexus\Modules\Shop\Models\Payment;
use Phlexus\Modules\Shop\Models\PaymentStatus;
use Phlexus\Modules\Shop\Models\PaymentType;
use Phlexus\PhlexusHelpers\Emails;

class SubscriptionTask extends Task
{
    public function createPaymentsAction()
    {
        $allOrders = Order::getAllRenewals();

        foreach ($allOrders as $order) {
            try {
                if (((int) $order->itemsCount) === 1) {
                    $payment = Payment::createPayment(
                        (float) $order->totalPrice,
                        PaymentType::RENEWAL,
                        (int) $order->paymentMethodID,
                        (int) $order->orderID
                    );
                    $this->sendRenewalPaymentCreatedEmail($payment, (int) $order->userID);
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

                    $payment = Payment::createPayment(
                        (float) $order->totalPrice,
                        PaymentType::RENEWAL,
                        (int) $newOrder->paymentMethodID,
                        (int) $newOrder->id
                    );
                    $this->sendRenewalPaymentCreatedEmail($payment, (int) $order->userID);
                }
            } catch(\Exception $e) {
                error_log('Failed to create renewal: ' . $e->getMessage(), 0);
            }
        }
    }

    private function sendRenewalPaymentCreatedEmail(Payment $payment, int $userID): void
    {
        $user = User::findFirst($userID);
        if (!$user) {
            return;
        }

        $company = Helpers::phlexusConfig('company')->toArray();

        $vars = [
            'companyName' => (string) ($company['name'] ?? 'Our team'),
            'userEmail'   => (string) $user->email,
            'totalPrice'  => number_format((float) $payment->totalPrice, 2),
            'reference'   => (string) $payment->hashCode,
            'createdAt'   => (string) ($payment->createdAt ?? ''),
        ];

        try {
            $body = Emails::renderEmail($this->buildEmailView(), 'payment', 'renewal_created', $vars);
            Emails::sendEmail((string) $user->email, 'Renewal payment created', $body);
        } catch (\Throwable $e) {
            error_log('Failed to send renewal payment email for user id=' . $userID . ': ' . $e->getMessage(), 0);
        }
    }

    private function buildEmailView(): View
    {
        $di     = $this->getDI();
        $theme  = Helpers::phlexusConfig('theme');
        $config = Helpers::phlexusConfig()->toArray();

        $view = new View();
        $view->setViewsDir((string) $theme->themes_dir . (string) $theme->theme_user . '/');

        $voltOptions = $config['view']['engines']['.volt']['options'] ?? [];

        $view->registerEngines([
            '.volt' => function (View $view) use ($di, $voltOptions) {
                $engine = new Volt($view, $di);
                if (!empty($voltOptions)) {
                    $engine->setOptions($voltOptions);
                }
                return $engine;
            },
        ]);

        return $view;
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