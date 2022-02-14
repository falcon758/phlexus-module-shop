<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
use Phalcon\Di;
use Phlexus\Modules\BaseUser\Models\User;

/**
 * Class Order
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Order extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const CANCELED = 0;

    public const CREATED = 1;

    public const PAID = 2;

    private const HASHLENGTH = 40;

    public $id;

    public $hashCode;
    
    public $paid;

    public $status;

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

        $this->hasMany('id', Item::class, 'orderID', ['alias' => 'items']);

        $this->hasMany('id', OrderAttributes::class, 'orderID', ['alias' => 'order_attributes']);
    }

    /**
     * Get order items
     * 
     * @return array
     */
    public function getItems(): array {
        $items = [];

        foreach ($this->items as $item) {
            $items[] = $item->Product->toArray();
        }

        return $items;
    }

    /**
     * Get order total price
     * 
     * @return float
     */
    public function getOrderTotal(): float {
        $items = $this->getItems();
    }

    /**
     * Cancel order
     * 
     * @return bool
     */
    public function cancelOrder(): bool {
        $this->status = self::CANCELED;
        return $this->save();
    }

    /**
     * Set order as paid
     * 
     * @return bool
     */
    public function paidOrder(): bool {
        $this->paid = 1;
        return $this->save();
    }

    /**
     * Is order paid
     * 
     * @return bool
     */
    public function isPaid(): bool {
        return $this->paid === 1;
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
     * 
     * @throws Exception
     */
    public static function createOrder(
        int $userId, int $billingId, int $shipmentID,
        int $paymentMethod, int $shippingMethod
    ): Order {
        $order = new self;
        $order->hashCode         = Di::getDefault()->getShared('security')->getRandom()->base64Safe(self::HASHLENGTH);
        $order->userID           = $userId;
        $order->billingID        = $billingId;
        $order->shipmentID       = $shipmentID;
        $order->paymentMethodID  = $paymentMethod;
        $order->shippingMethodID = $shippingMethod;
        $order->status = self::CREATED;

        if (!$order->save()) {
            throw new \Exception('Unable to process order');
        }

        return $order;
    }

    /**
     * Get Multiple Attributes
     * 
     * @param array $names Array of names to retrieve
     * 
     * @return array
     */
    public function getAttributes(array $names): array
    {

    }

    /**
     * Set Multiple Attributes
     * 
     * @param array $attributes Array of names to set
     * 
     * @return bool
     */
    public function setAttributes(array $attributes): bool
    {
        foreach ($attributes as $key => $value) {
            $attribute          = new OrderAttributes();
            $attribute->name    = $key;
            $attribute->value   = $value;
            $attribute->orderID = $this->id;
            if (!$attribute->save()) {
                return false;
            }
        }

        return true;
    }
}
