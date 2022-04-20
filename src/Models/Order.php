<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Modules\BaseUser\Models\User;
use Phalcon\Mvc\Model;
use Phalcon\Di;

/**
 * Class Order
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Order extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    private const HASHLENGTH = 40;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public string $hashCode;
    
    /**
     * @var int|null
     */
    public $paid;

    /**
     * @var int|null
     */
    public $statusID;

    /**
     * @var int|null
     */
    public $active;

    /**
     * @var int
     */
    public int $userID;

    /**
     * @var int
     */
    public int $billingID;

    /**
     * @var int
     */
    public int $shipmentID;

    /**
     * @var int
     */
    public int $paymentMethodID;

    /**
     * @var int
     */
    public int $shippingMethodID;

    /**
     * @var string|null
     */
    public $createdAt;

    /**
     * @var string|null
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

        $this->hasMany('id', Payment::class, 'orderID', ['alias' => 'payments']);
    }

    /**
     * Get order items
     * 
     * @return array
     */
    public function getItems(): array
    {
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
    public function getOrderTotal(): float
    {
        $items = $this->getItems();
    }

    /**
     * Cancel order
     * 
     * @return bool
     */
    public function cancelOrder(): bool
    {
        $this->statusID = OrderStatus::CANCELED;
        $this->paid = self::DISABLED;

        return $this->save();
    }

    /**
     * Set order as paid
     * 
     * @return bool
     */
    public function paidOrder(): bool
    {
        $this->statusID = OrderStatus::DONE;

        if ($this->hasSubscriptionItem()) {
            $this->statusID = OrderStatus::RENEWAL;
        }

        $this->paid = self::ENABLED;

        return $this->save();
    }

    /**
     * Is order paid
     * 
     * @return bool
     */
    public function isPaid(): bool
    {
        return ((int) $this->paid) === self::PAID;
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
        $order->statusID         = OrderStatus::CREATED;

        if (!$order->save()) {
            throw new \Exception('Unable to process order');
        }

        return $order;
    }

    /**
     * Has subscription item
     * 
     * @return bool
     */
    public function hasSubscriptionItem(): bool
    {
        $items = $this->items;

        if (!$items) {
            return false;
        }

        foreach ($items as $item) {
            if ($item->hasSubscriptionProduct()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Has subscription active
     * 
     * @param int $productID Product to search for
     * 
     * @return bool
     */
    public function hasSubscriptionActive(int $productID = 0): bool
    {
        $items = $this->getSubscriptionItems();
        if (count($items) === 0) {
            return false;
        }

        foreach ($items as $item) {
            
        }

        return false;
    }

    /**
     * Get subscription items
     * 
     * @return array
     */
    public function getSubscriptionItems(): array
    {
        $items = $this->items;

        if (!$items) {
            return [];
        }

        $subscriptions = [];
        foreach ($items as $item) {
            if ($item->hasSubscriptionProduct()) {
                $subscriptions[] = $item;
            }
        }

        return $subscriptions;
    }

    /**
     * Get last payment
     * 
     * @return Payment|null
     */
    public function getLastPayment()
    {
        return $this->payment->first();
    }

    /**
     * Get last payment by product
     * 
     * @param int $productID Product to search for
     *
     * @return Payment|null
     */
    public function getLastPaymentByProduct(int $productID)
    {
        return null;
        //return $this->payment->first();
    }
}
