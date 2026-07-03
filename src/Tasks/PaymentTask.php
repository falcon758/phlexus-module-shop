<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Tasks;

use Phalcon\Cli\Task;
use Phlexus\Modules\Shop\Models\Payment;
use Phlexus\Modules\Shop\Models\PaymentStatus;
use Phlexus\Modules\Shop\Models\PaymentType;

class PaymentTask extends Task
{
    /**
     * Cancel all active CREATED non-renewal payments that are older than 1 day
     * (i.e. initial purchase payments the user never completed).
     */
    public function cancelExpiredAction()
    {
        $p_model = Payment::class;

        $expired = Payment::query()
            ->where(
                "$p_model.active = :active:
                AND $p_model.statusID = :statusID:
                AND $p_model.paymentTypeID = :paymentTypeID:
                AND DATEDIFF(CURRENT_DATE(), $p_model.createdAt) >= 1",
                [
                    'active'        => Payment::ENABLED,
                    'statusID'      => PaymentStatus::CREATED,
                    'paymentTypeID' => PaymentType::PAYMENT,
                ]
            )
            ->execute();

        foreach ($expired as $payment) {
            if (!$payment->cancelPayment()) {
                error_log('Failed to cancel expired payment id=' . $payment->id, 0);
            }
        }
    }
}
