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

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $hashCode;
    
    /**
     * @var int
     */
    public $paid;

    /**
     * @var int
     */
    public $status;

    /**
     * @var int
     */
    public $active;

    /**
     * @var int
     */
    public $userID;

    /**
     * @var int
     */
    public $billingID;

    /**
     * @var int
     */
    public $shipmentID;

    /**
     * @var int
     */
    public $paymentMethodID;

    /**
     * @var int
     */
    public $shippingMethodID;

    /**
     * @var string
     */
    public $createdAt;

    /**
     * @var string
     */
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
            'alias'    => 'billingAddress',
            'reusable' => true,
        ]);

        $this->hasOne('shipmentID', UserAddress::class, 'id', [
            'alias'    => 'shipmentAddress',
            'reusable' => true,
        ]);

        $this->hasOne('paymentMethodID', PaymentMethod::class, 'id', [
            'alias'    => 'paymentMethod',
            'reusable' => true,
        ]);

        $this->hasOne('shippingMethodID', ShippingMethod::class, 'id', [
            'alias'    => 'shippingMethod',
            'reusable' => true,
        ]);

        $this->hasMany('id', Item::class, 'orderID', ['alias' => 'items']);

        $this->hasMany('id', OrderAttributes::class, 'orderID', ['alias' => 'orderAttributes']);
    }

    /**
     * Get order items
     * 
     * @return array
     */
    public function getItems(): array {
        $items = [];

        foreach ($this->items as $item) {
            $items[] = $item->product->toArray();
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
     * @param int $userID           User to assign order to
     * @param int $billingID        Billing id to assign
     * @param int $shipmentID       Shipment id to assign
     * @param int $paymentMethodID  Payment method id to assign
     * @param int $shippingMethodID Shipping method id to assign
     *
     * @return Order
     * 
     * @throws Exception
     */
    public static function createOrder(
        int $userID, int $billingID, int $shipmentID,
        int $paymentMethod, int $shippingMethod
    ): Order {
        $order = new self;
        $order->hashCode         = Di::getDefault()->getShared('security')->getRandom()->base64Safe(self::HASHLENGTH);
        $order->userID           = $userID;
        $order->billingID        = $billingID;
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
        if (count($names) === 0) {
            return [];
        }

        $inQuery = '?' . implode(', ?', range(1, count($names)));

        $values = array_merge([$this->id], $names);

        $attributes = OrderAttributes::find(
            [
                'orderID = ?0 AND name IN (' . $inQuery . ')',
                'bind' => $values
            ]
        );

        return $attributes->toArray();
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
