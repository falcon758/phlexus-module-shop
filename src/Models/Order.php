<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
use Phlexus\Modules\BaseUser\Models\User;
use Phlexus\Modules\Shop\Models\UserAddress;
use Phlexus\Modules\Shop\Models\PaymentMethod;
use Phlexus\Modules\Shop\Models\ShippingMethod;

/**
 * Class Order
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Order extends Model
{
    const DISABLED = 0;

    const ENABLED = 1;

    public $id;

    public $active;

    public $userID;

    public $billingID;

    public $shipmentID;

    public $paymentMethodID;

    public $shippingMethodID;

    public $createdAt;

    public $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('orders');

        $this->hasOne('userID', User::class, 'id', [
            'alias'    => 'user',
            'reusable' => true,
        ]);

        $this->hasOne('billingID', UserAddress::class, 'id', [
            'alias'    => 'billing_address',
            'reusable' => true,
        ]);

        $this->hasOne('shipmentID', UserAddress::class, 'id', [
            'alias'    => 'shipment_address',
            'reusable' => true,
        ]);

        $this->hasOne('paymentMethodID', PaymentMethod::class, 'id', [
            'alias'    => 'payment_method',
            'reusable' => true,
        ]);

        $this->hasOne('shippingMethodID', ShippingMethod::class, 'id', [
            'alias'    => 'shipping_method',
            'reusable' => true,
        ]);
    }

    /**
     * Create order
     * 
     * @param int $userId           User to assign order to
     * @param int $billingId        Billing id to assign
     * @param int $shipmentID       Shipment id to assign
     * @param int $paymentMethodID  Payment method id to assign
     * @param int $shippingMethodID Shipping method id to assign
     *
     * @return Order
     */
    public static function createOrder(
        int $userId, int $billingId, int $shipmentID,
        int $paymentMethod, int $shippingMethod
    ): Order {
        $order = new self;
        $order->userID = $userId;
        $order->billingID = $billingId;
        $order->shipmentID = $shipmentID;
        $order->paymentMethodID = $paymentMethod;
        $order->shippingMethodID = $shippingMethod;

        return $order->save() ? $order : null;
    }
}
