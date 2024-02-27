<?php
declare(strict_types=1);

namespace Phlexus\Modules\BaseUser\Events\Listeners;

use Phlexus\Modules\Shop\Models\Payment;
use Phalcon\Di\Injectable;
use Phalcon\Events\Event;
use Phalcon\Mvc\DispatcherInterface;

final class StockListener extends Injectable
{
    /**
     * This action is executed after execute all action in the application.
     *
     * @param Event $event Event object.
     * @param DispatcherInterface $dispatcher Dispatcher object.
     * @param array $data The event data.
     *
     * @return bool
     */
    public function afterDispatchLoop(Event $event, DispatcherInterface $dispatcher, $data = null)
    {
        $this->getDI()->getShared('eventsManager')->attach(
            'payment:success',
            function (Event $event, Payment $payment) {
                return $payment->order->applyStockDiscount();
            }
        );

        return !$event->isStopped();
    }
}
