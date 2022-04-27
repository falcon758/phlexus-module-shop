<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Modules\BaseUser\Models\User;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
use Phalcon\Mvc\Model\Resultset\Simple;
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
     * @var int|null
     */
    public $parentID;

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
        int $paymentMethodID, int $shippingMethodID,
        int $relatedOrder = 0, int $statusID = OrderStatus::CREATED
    ): Order {
        $order = new self;
        $order->hashCode         = Di::getDefault()->getShared('security')->getRandom()->base64Safe(self::HASHLENGTH);
        $order->userID           = $userID;
        $order->billingID        = $billingID;
        $order->shipmentID       = $shipmentID;
        $order->paymentMethodID  = $paymentMethodID;
        $order->shippingMethodID = $shippingMethodID;
        $order->statusID         = OrderStatus::CREATED;

        if ($relatedOrder > 0) {
            $order->parentID = $relatedOrder;
        }

        if (!$order->save()) {
            throw new \Exception('Unable to process order');
        }

        return $order;
    }

    /**
     * Renewal order
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
    public static function renewalOrder(
        int $userID, int $billingID, int $shipmentID,
        int $paymentMethodID, int $shippingMethodID,
        int $relatedOrder = 0, int $statusID = OrderStatus::CREATED
    ): Order {
        return self::createOrder(
            $userID, $billingID, $shipmentID,
            $paymentMethodID, $shippingMethodID,
            $relatedOrder = 0, OrderStatus::RENEWAL
        );
    }

    /**
     * Create items
     * 
     * @param array $productsID Products id to assign
     *
     * @return bool
     */
    public function createItems(array $productsID): bool {
        // Create a transaction manager
        $manager = new TxManager();

        // Request a transaction
        $transaction = $manager->get();

        try {
            foreach ($productsID as $prodID) {
                $item = new Item;
                $item->setTransaction($transaction);

                $product = Product::findFirstByid((int) $prodID);

                if (!$product) {
                    throw new \Exception('Product doesn\'t exists');
                }

                $item->productID = (int) $product->id;
                $item->orderID   = (int) $this->id;

                if (!$item->save()) {
                    $transaction->rollback();
                    return false;
                }
            }

            $transaction->commit();
        } catch (TxFailed $e) {
            $transaction->rollback();
            return false;
        }

        return true;
    }

    /**
     * Get last order
     * 
     * @return Order
     */
    public function getLastOrder(): Order
    {
        $lastOrder = $this->getDi()->getShared('db')->prepare('
            WITH RECURSIVE orderRecursive AS (
                SELECT id, parentID
                FROM orders
                where id = :orderID
                UNION ALL
                SELECT secondOrder.id, orderRecursive.parentID
                FROM orderRecursive INNER JOIN orders secondOrder
                on secondOrder.parentID = orderRecursive.id
            )
            SELECT id
            FROM orderRecursive
            ORDER BY id DESC
            LIMIT 1;
        ');

        $lastOrder->bindParam(':orderID', $this->id);
        $lastOrder->execute();
        $result = $lastOrder->fetch(\PDO::FETCH_ASSOC);
        
        return ($result['id'] === $this->id) ? $this : self::findFirstByid($result['id']);
    }

    /**
     * Has subscription item
     * 
     * @return bool
     */
    public function hasSubscriptionItem(): bool
    {
        return self::query()
            ->innerJoin(Item::class, null, 'I')
            ->innerJoin(Product::class, 'I.productID = PR.id', 'PR')
            ->where('I.orderID = :orderID: AND PR.isSubscription = :isSubscription:', [
                'isSubscription' => 1,
                'orderID'        => $this->id
            ])
            ->execute()
            ->count() > 0;
    }

    /**
     * Has subscription active
     * 
     * @param int $productID Product to search for
     * 
     * @return array|bool
     * 
     * @throws Exception
     */
    public function hasSubscriptionActive(int $productID)
    {
        $p_model = self::class;

        return self::query()
            ->innerJoin(Item::class, null, 'I')
            ->innerJoin(Product::class, 'I.productID = PR.id', 'PR')
            ->innerJoin(ProductAttribute::class, 'PR.id = Period.productID AND Period.name = "' . ProductAttribute::SUBSCRIPTION_PERIOD . '"', 'Period')
            ->innerJoin(ProductAttribute::class, 'PR.id = MaxDelay.productID AND MaxDelay.name = "' . ProductAttribute::SUBSCRIPTION_MAX_DELAY . '"', 'MaxDelay')
            ->innerJoin(Payment::class, "$p_model.id = PST.orderID", 'PST')
            ->leftJoin(Payment::class, "
                $p_model.id = PSD.orderID
            AND 
                (
                        PST.createdAt < PSD.createdAt 
                    OR (
                        PST.createdAt = PSD.createdAt AND PST.id < PSD.id
                    )
                )", 'PSD')
            ->where(
                "$p_model.active = :active: 
                AND $p_model.id = :orderID:
                AND $p_model.statusID = :status: 
                AND I.active = :active: 
                AND PR.id = :productID:
                AND PR.isSubscription = :isSubscription: 
                AND DATEDIFF(CURRENT_DATE(), PST.createdAt) <= Period.value + MaxDelay.value",
                [
                    'active'         => self::ENABLED,
                    'orderID'        => $this->id,
                    'status'         => OrderStatus::RENEWAL,
                    'productID'      => $productID,
                    'isSubscription' => 1
                ]
            )->orderBy($p_model . '.id DESC')
            ->execute()
            ->count() === 1;
    }

    /**
     * Get subscription items
     * 
     * @param int $productID Product to search for
     * 
     * @return Simple
     */
    public function getSubscriptionItems(int $productID = 0): Simple
    {
        $whereCond   = 'I.orderID = :orderID: AND PR.isSubscription = :isSubscription:';
        $whereValues = [
            'isSubscription' => 1,
            'orderID'        => $this->id
        ];

        if ($productID !== 0) {
            $whereCond .= ' AND PR.id = :productID:';
            $whereValues['productID'] = $productID;
        }

        return self::query()
            ->columns('I.*')
            ->innerJoin(Item::class, null, 'I')
            ->innerJoin(Product::class, 'I.productID = PR.id', 'PR')
            ->where($whereCond, $whereValues)
            ->execute();
    }

    /**
     * Get last payment
     * 
     * @return Payment|null
     */
    public function getLastPayment()
    {
        return self::query()
            ->columns('P.*')
            ->innerJoin(Payment::class, null, 'P')
            ->where('P.orderID = :orderID:', [
                'orderID' => $this->id
            ])
            ->orderBy('P.id DESC')
            ->execute()
            ->getFirst();
    }

    /**
     * Get last paid payment
     * 
     * @return Payment|null
     */
    public function getLastPaidPayment()
    {
        return self::query()
            ->columns('P.*')
            ->innerJoin(Payment::class, null, 'P')
            ->where('P.status = :status: AND P.orderID = :orderID:', [
                'status'  => PaymentStatus::PAID,
                'orderID' => $this->id
            ])
            ->orderBy('P.id DESC')
            ->execute()
            ->getFirst();
    }

    /**
     * Get last order by product
     * 
     * @param int $userID    User assigned to
     * @param int $productID Product to search for
     *
     * @return Order|null
     */
    public static function getLastOrderByUserProduct(int $userID, int $productID)
    {
        return self::query()
            ->innerJoin(User::class, null, 'U')
            ->innerJoin(Item::class, null, 'I')
            ->where('U.id = :userID: AND I.productID = :productID:', [
                'userID'    => $userID,
                'productID' => $productID
            ])
            ->orderBy(self::class . '.id DESC')
            ->execute()
            ->getFirst();
    }

    /**
     * Get all renewals
     *
     * @return Simple
     */
    public static function getAllRenewals(): Simple
    {
        $p_model = self::class;
        $i_model = Item::class;

        return self::query()
            ->columns("
                $p_model.id AS orderID,
                $p_model.userID,
                $p_model.billingID,
                $p_model.shipmentID,
                $p_model.paymentMethodID,
                $p_model.shippingMethodID,
                I.id AS itemID,
                I.productID,
                (SELECT COUNT($i_model.id) FROM $i_model WHERE $i_model.orderID = $p_model.id) AS itemsCount
            ")
            ->innerJoin($i_model, null, 'I')
            ->innerJoin(Product::class, 'I.productID = PR.id', 'PR')
            ->innerJoin(ProductAttribute::class, 'PR.id = Period.productID AND Period.name = "' . ProductAttribute::SUBSCRIPTION_PERIOD . '"', 'Period')
            ->innerJoin(ProductAttribute::class, 'PR.id = SOffset.productID AND SOffset.name = "' . ProductAttribute::SUBSCRIPTION_PAYMENT_OFFSET . '"', 'SOffset')
            ->innerJoin(Payment::class, "$p_model.id = PST.orderID", 'PST')
            ->leftJoin(Payment::class, "
                $p_model.id = PSD.orderID
            AND 
                (
                        PST.createdAt < PSD.createdAt 
                    OR (
                        PST.createdAt = PSD.createdAt AND PST.id < PSD.id
                    )
                )", 'PSD')
            ->where(
                "$p_model.active = :active: 
                AND $p_model.statusID = :status: 
                AND I.active = :active: 
                AND PR.isSubscription = :isSubscription: 
                AND DATEDIFF(CURRENT_DATE(), PST.createdAt) >= Period.value - SOffset.value",
                [
                    'active'         => self::ENABLED,
                    'status'         => OrderStatus::RENEWAL,
                    'isSubscription' => 1
                ]
            )->orderBy($p_model . '.id DESC')
            ->execute();
    }

    /**
     * Get all expired
     *
     * @return Simple
     */
    public static function getAllExpired(): Simple
    {
        $p_model = self::class;

        return self::query()
            ->columns('I.*')
            ->innerJoin(Item::class, null, 'I')
            ->innerJoin(Product::class, 'I.productID = PR.id', 'PR')
            ->innerJoin(ProductAttribute::class, 'PR.id = Period.productID AND Period.name = "' . ProductAttribute::SUBSCRIPTION_PERIOD . '"', 'Period')
            ->innerJoin(ProductAttribute::class, 'PR.id = MaxDelay.productID AND MaxDelay.name = "' . ProductAttribute::SUBSCRIPTION_MAX_DELAY . '"', 'MaxDelay')
            ->innerJoin(Payment::class, "$p_model.id = PST.orderID", 'PST')
            ->leftJoin(Payment::class, "
                $p_model.id = PSD.orderID
            AND 
                (
                        PST.createdAt < PSD.createdAt 
                    OR (
                        PST.createdAt = PSD.createdAt AND PST.id < PSD.id
                    )
                )", 'PSD')
            ->where(
                "$p_model.active = :active: 
                AND $p_model.statusID = :status: 
                AND I.active = :active: 
                AND PR.isSubscription = :isSubscription: 
                AND PST.statusID != :paymentStatus:
                AND DATEDIFF(CURRENT_DATE(), PST.createdAt) > MaxDelay.value",
                [
                    'active'         => self::ENABLED,
                    'status'         => OrderStatus::RENEWAL,
                    'isSubscription' => 1,
                    'paymentStatus'  => PaymentStatus::PAID
                ]
            )->orderBy($p_model . '.id DESC')
            ->execute();
    }
}
