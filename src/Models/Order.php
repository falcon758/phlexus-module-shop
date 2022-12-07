<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Modules\BaseUser\Models\User;
use Phlexus\Models\Model;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
use Phalcon\Paginator\Adapter\QueryBuilder;
use Phalcon\Paginator\Repository;
use Phalcon\Mvc\Model\Resultset\Simple;
use Phalcon\Di\Di;

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
        $p_model = self::class;

        return self::query()
            ->columns('
                I.productID AS productID,
                PR.name AS productName,
                I.quantity AS quantity,
                I.price AS price,
                (I.quantity * I.price) AS totalPrice
            ')
            ->innerJoin(Item::class, null, 'I')
            ->innerJoin(Product::class, 'I.productID = PR.id', 'PR')
            ->where("
                $p_model.active = :orderActive: 
                AND $p_model.id = :orderID:
                AND I.active = :itemActive:
            ",
                [
                    'orderActive'  => self::ENABLED,
                    'itemActive'  => Item::ENABLED,
                    'orderID' => $this->id
                ]
            )
            ->orderBy("$p_model.id DESC")
            ->execute()
            ->toArray();
    }

    /**
     * Get order total price
     * 
     * @return float
     */
    public function getOrderTotal(): float
    {
        $total = 0.00;
        foreach ($this->items as $item) {
            $total += $item->price * $item->quantity;
        }

        return $total;
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
     * @param array $products Products and quantities to assign
     *
     * @return bool
     */
    public function createItems(array $products): bool {
        // Create a transaction manager
        $manager = new TxManager();

        // Request a transaction
        $transaction = $manager->get();

        try {
            foreach ($products as $prodID => $quantity) {
                $item = new Item;
                $item->setTransaction($transaction);

                $product = Product::findFirstByid((int) $prodID);

                if (!$product) {
                    throw new \Exception('Product doesn\'t exists');
                }

                $item->quantity  = (int) $quantity;
                $item->price     = (float) $product->price;
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
                "$p_model.active = :orderActive: 
                AND $p_model.id = :orderID:
                AND $p_model.statusID = :orderStatus: 
                AND I.active = :itemActive: 
                AND PR.id = :productID:
                AND PR.isSubscription = :isSubscription: 
                AND DATEDIFF(CURRENT_DATE(), PST.createdAt) <= Period.value + MaxDelay.value
                AND PSD.id IS NULL",
                [
                    'orderActive'    => self::ENABLED,
                    'orderID'        => $this->id,
                    'orderStatus'    => OrderStatus::RENEWAL,
                    'itemActive'     => Item::ENABLED,
                    'productID'      => $productID,
                    'isSubscription' => 1
                ]
            )
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
    public function getLastPayment(): ?Payment
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
    public function getLastPaidPayment(): ?Payment
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
    public static function getLastOrderByUserProduct(int $userID, int $productID): ?Order
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
     * Get order by Hash
     * 
     * @param string $hashCode
     *
     * @return Simple|null
     */
    public static function getOrderByHash(string $hashCode): ?Simple
    {
        $user = User::getUser();

        if (!$user) {
            throw new \Exception('User not found!');
        }

        $p_model = self::class;

        return self::query()
            ->columns('
                U.email AS userEmail,

                BA.address AS billingAddress,
                BP.postCode AS billingPostCode,
                BC.country AS billingCountry,

                VT.tax AS vatTax,

                SA.address AS shipmentAddress,
                SP.postCode AS shipmentPostCode,
                SC.country AS shipmentCountry,

                P.invoiceNumber AS invoiceNumber,

                PM.name AS paymentMethod,
                SM.name AS shippingMethod,

                I.productID AS productID,
                I.quantity AS quantity,
                I.price AS price,
                SUM(L.quantity) AS totalQuantities,
                SUM(L.price) AS totalPrice
            ')
            ->innerJoin(User::class, null, 'U')

            ->innerJoin(UserAddress::class, "$p_model.billingID = B.id", 'B')
            ->innerJoin(Address::class, 'B.addressID = BA.id', 'BA')
            ->innerJoin(PostCode::class, 'BA.postCodeID = BP.id', 'BP')
            ->innerJoin(Locale::class, 'BP.localeID = BL.id', 'BL')
            ->innerJoin(Country::class, 'BL.countryID = BC.id', 'BC')

            ->innerJoin(VATTax::class, 'BC.id = VT.countryID', 'VT')

            ->innerJoin(UserAddress::class, "$p_model.shipmentID = S.id", 'S')
            ->innerJoin(Address::class, 'S.addressID = SA.id', 'SA')
            ->innerJoin(PostCode::class, 'SA.postCodeID = SP.id', 'SP')
            ->innerJoin(Locale::class, 'SP.localeID = SL.id', 'SL')
            ->innerJoin(Country::class, 'SL.countryID = SC.id', 'SC')

            ->innerJoin(Payment::class, null, 'P')

            ->innerJoin(PaymentMethod::class, null, 'PM')
            ->innerJoin(ShippingMethod::class, null, 'SM')

            ->innerJoin(Item::class, null, 'I')
            ->innerJoin(Item::class, 'I.orderID = L.orderID', 'L')
            ->where(
                "$p_model.active = :active: 
                AND $p_model.userID = :userID: 
                AND $p_model.hashCode = :hashCode:",
                [
                    'active'   => self::ENABLED,
                    'userID'   => $user->id,
                    'hashCode' => $hashCode
                ]
            )
            ->orderBy("$p_model.id DESC")
            ->groupBy('P.id, I.id, L.orderID, VT.id')
            ->execute();
    }

    /**
     * Get history
     * 
     * @return Repository
     * 
     * @throws Exception
     */
    public static function getHistory(int $page = 1): Repository
    {
        $user = User::getUser();

        if (!$user) {
            throw new \Exception('User not found!');
        }

        $p_model = self::class;

        $query = self::query()
            ->createBuilder()
            ->columns("
                $p_model.id AS orderID,
                $p_model.hashCode as hashCode,
                $p_model.createdAt as createdAt,
                I.productID AS productID,
                I.quantity AS quantity,
                I.price AS price,
                SUM(L.quantity) AS totalQuantities,
                SUM(L.price) AS totalPrice
            ")
            ->innerJoin(Item::class, null, 'I')
            ->innerJoin(Item::class, 'I.orderID = L.orderID', 'L')
            ->where(
                "$p_model.active = :active: 
                AND $p_model.userID = :userID:",
                [
                    'active' => self::ENABLED,
                    'userID' => $user->id,
                ]
            )
            ->orderBy("$p_model.id DESC")
            ->groupBy('I.id, L.orderID');

        return (
            new QueryBuilder(
            [
                'builder' => $query,
                'limit'   => self::PAGE_LIMIT,
                'page'    => $page,
            ]
            )
        )->paginate();
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
                I.quantity,
                I.productID,
                (I.price * I.quantity) AS totalPrice,
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
                AND DATEDIFF(CURRENT_DATE(), PST.createdAt) >= Period.value - SOffset.value
                AND PSD.id IS NULL",
                [
                    'active'         => self::ENABLED,
                    'status'         => OrderStatus::RENEWAL,
                    'isSubscription' => 1
                ]
            )->orderBy("$p_model.id DESC")
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
            ->innerJoin(ProductAttribute::class, 'PR.id = SOffset.productID AND SOffset.name = "' . ProductAttribute::SUBSCRIPTION_PAYMENT_OFFSET . '"', 'SOffset')
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
                AND DATEDIFF(CURRENT_DATE(), PST.createdAt) > (SOffset.value + MaxDelay.value)
                AND PSD.id IS NULL",
                [
                    'active'         => self::ENABLED,
                    'status'         => OrderStatus::RENEWAL,
                    'isSubscription' => 1,
                    'paymentStatus'  => PaymentStatus::PAID
                ]
            )->orderBy("$p_model.id DESC")
            ->execute();
    }
}
