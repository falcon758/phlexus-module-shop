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
use Phalcon\Di;

/**
 * Class Payment
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Payment extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    private const HASHLENGTH = 40;

    private const INVOICEPAD = 7;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public string $hashCode;

    /**
     * @var float
     */
    public float $totalPrice;

    /**
     * @var string
     */
    public $invoiceNumber;

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
    public int $paymentMethodID;

    /**
     * @var int
     */
    public int $paymentTypeID;
    
    /**
     * @var int
     */
    public int $orderID;

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
        $this->setSource('payments');

        $this->hasOne('statusID', OrderStatus::class, 'id', [
            'alias'    => 'orderStatus',
            'reusable' => true,
        ]);

        $this->hasOne('paymentMethodID', PaymentMethod::class, 'id', [
            'alias'    => 'paymentMethod',
            'reusable' => true,
        ]);

        $this->hasOne('paymentTypeID', PaymentType::class, 'id', [
            'alias'    => 'paymentType',
            'reusable' => true,
        ]);

        $this->hasOne('orderID', Order::class, 'id', [
            'alias'    => 'order',
            'reusable' => true,
        ]);

        $this->hasMany('id', PaymentAttribute::class, 'paymentID', ['alias' => 'paymentAttribute']);
    }

    /**
     * After create
     * 
     * @todo Save record
     */
    public function afterCreate()
    {
        $this->invoiceNumber = self::generateInvoiceNumber((int) $this->id);
        //$this->save();

        // Save workaround
        $saveInvoiceNumber = Di::getDefault()->getShared('db')->prepare(
            'UPDATE
                payments 
            SET
                invoiceNumber = :invoiceNumber 
            WHERE
                id = :id'
        );

        $saveInvoiceNumber->bindParam(':invoiceNumber', $this->invoiceNumber);
        $saveInvoiceNumber->bindParam(':id', $this->id);

        $saveInvoiceNumber->execute();
    }

    /**
     * Cancel payment
     * 
     * @return bool
     */
    public function cancelPayment(): bool
    {
        $this->statusID = PaymentStatus::CANCELED;
        return $this->save();
    }

    /**
     * Set as paid
     * 
     * @return bool
     */
    public function paid(): bool
    {
        $this->statusID = PaymentStatus::PAID;
        $this->modifiedAt = date('Y-m-d H:i:s', time());

        $this->order->paidOrder();

        return $this->save();
    }

    /**
     * Is paid
     * 
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->statusID === PaymentStatus::PAID;
    }

    /**
     * Create payment
     * 
     * @param float $totalPrice      Total price assign
     * @param int   $paymentTypeID   Payment type id to assign
     * @param int   $paymentMethodID Payment method id to assign
     * @param int   $orderID         Order id to assign
     *
     * @return Payment
     * 
     * @throws Exception
     */
    public static function createPayment(float $totalPrice, int $paymentTypeID, int $paymentMethodID, int $orderID): Payment
    {
        $payment = new self;
        $payment->hashCode         = Di::getDefault()->getShared('security')->getRandom()->base64Safe(self::HASHLENGTH);
        $payment->totalPrice       = $totalPrice;
        $payment->paymentTypeID    = $paymentTypeID;
        $payment->paymentMethodID  = $paymentMethodID;
        $payment->orderID          = $orderID;
        $payment->statusID         = PaymentStatus::CREATED;

        if (!$payment->save()) {
            throw new \Exception('Unable to process payment');
        }

        return $payment;
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

        $inQuery = '?' . implode(', ?', range(2, count($names)));

        $values = array_merge([1, $this->id], $names);

        $attributes = PaymentAttribute::find(
            [
                'active = ?0 
                AND paymentID = ?1 
                AND name IN (' . $inQuery . ')',
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
        // Create a transaction manager
        $manager = new TxManager();

        // Request a transaction
        $transaction = $manager->get();
        
        try {
            foreach ($attributes as $key => $value) {
                $attribute = new PaymentAttribute();
                $attribute->setTransaction($transaction);
                $attribute->name      = (string) $key;
                $attribute->value     = (string) $value;
                $attribute->paymentID = (int) $this->id;

                if (!$attribute->save()) {
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
     * Get user payment
     *
     * @param string $paymentHash Payment to search for
     *
     * @return Payment|null
     * 
     * @throws Exception
     */
    public static function getUserPayment(string $paymentHash): ?Payment {
        
        $p_model = self::class;

        $user = User::getUser();

        if (!$user) {
            throw new \Exception('User not found!');
        }

        return self::query()
            ->columns("$p_model.*")
            ->innerJoin(Order::class, null, 'O')
            ->where("$p_model.statusID = :paymentStatus: 
                AND $p_model.hashCode = :hashCode: 
                AND $p_model.active = :paymentActive: 
                AND O.active = :orderActive: 
                AND O.userID = :userID:",
            [
                'paymentStatus'   => PaymentStatus::CREATED,
                'paymentActive' => self::ENABLED,
                'orderActive' => Order::ENABLED,
                'hashCode' => $paymentHash,
                'userID'   => $user->id,
            ])
            ->orderBy("$p_model.id DESC")
            ->execute()
            ->getFirst();
    }

    /**
     * Get last paid by product
     * 
     * @param int $userID    User assigned to
     * @param int $productID Product to search for
     *
     * @return Payment|null
     */
    public static function getLastPaidByUserProduct(int $userID, int $productID): ?Payment
    {
        $p_model = self::class;

        return self::query()
            ->columns("$p_model.*")
            ->innerJoin(Order::class, null, 'O')
            ->innerJoin(Item::class, 'O.id = I.orderID', 'I')
            ->where(" $p_model.statusID = :status: AND O.userID = :userID: AND I.productID = :productID:", [
                'status'    => PaymentStatus::PAID,
                'userID'    => $userID,
                'productID' => $productID,
            ])
            ->orderBy("$p_model.id DESC")
            ->execute()
            ->getFirst();
    }

    /**
     * Get payment by Hash
     * 
     * @param string $hashCode
     *
     * @return Simple|null
     */
    public static function getPaymentByHash(string $hashCode): ?Simple
    {
        $user = User::getUser();

        if (!$user) {
            throw new \Exception('User not found!');
        }

        $p_model = self::class;

        return self::query()
            ->columns("
                $p_model.invoiceNumber AS invoiceNumber,

                U.email AS userEmail,

                BA.address AS billingAddress,
                BP.postCode AS billingPostCode,
                BC.country AS billingCountry,

                SA.address AS shipmentAddress,
                SP.postCode AS shipmentPostCode,
                SC.country AS shipmentCountry,

                PM.name AS paymentMethod,
                SM.name AS shippingMethod,

                I.productID AS productID,
                I.quantity AS quantity,
                I.price AS price,
                SUM(L.quantity) AS totalQuantities,
                SUM(L.price) AS totalPrice
            ")
            ->innerJoin(Order::class, null, 'O')

            ->innerJoin(User::class, 'O.userID = U.id', 'U')

            ->innerJoin(UserAddress::class, 'O.billingID = B.id', 'B')
            ->innerJoin(Address::class, 'B.addressID = BA.id', 'BA')
            ->innerJoin(PostCode::class, 'BA.postCodeID = BP.id', 'BP')
            ->innerJoin(Locale::class, 'BP.localeID = BL.id', 'BL')
            ->innerJoin(Country::class, 'BL.countryID = BC.id', 'BC')

            ->innerJoin(UserAddress::class, 'O.shipmentID = S.id', 'S')
            ->innerJoin(Address::class, 'S.addressID = SA.id', 'SA')
            ->innerJoin(PostCode::class, 'SA.postCodeID = SP.id', 'SP')
            ->innerJoin(Locale::class, 'SP.localeID = SL.id', 'SL')
            ->innerJoin(Country::class, 'SL.countryID = SC.id', 'SC')

            ->innerJoin(PaymentMethod::class, 'O.paymentMethodID = PM.id', 'PM')
            ->innerJoin(ShippingMethod::class, 'O.shippingMethodID = SM.id', 'SM')

            ->innerJoin(Item::class, 'O.id = I.orderID', 'I')
            ->innerJoin(Item::class, 'I.orderID = L.orderID', 'L')
            ->where(
                "$p_model.hashCode = :hashCode:
                AND $p_model.statusID = :paymentStatus: 
                AND $p_model.active = :paymentActive: 
                AND O.active = :orderActive: 
                AND O.userID = :userID:",
                [
                    'hashCode'      => $hashCode,
                    'paymentStatus' => PaymentStatus::PAID,
                    'paymentActive' => self::ENABLED,
                    'orderActive'   => Order::ENABLED,
                    'userID'        => $user->id,
                ]
            )
            ->orderBy("$p_model.id DESC")
            ->groupBy("$p_model.id, I.id, L.orderID")
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
                $p_model.id as paymentID,
                $p_model.createdAt as createdAt,
                $p_model.modifiedAt as modifiedAt,
                $p_model.hashCode as hashCode,
                I.productID AS productID,
                I.quantity AS quantity,
                I.price AS price,
                SUM(L.quantity) AS totalQuantities,
                SUM(L.price) AS totalPrice
            ")
            ->innerJoin(Order::class, null, 'O')
            ->innerJoin(Item::class, 'O.id = I.orderID', 'I')
            ->innerJoin(Item::class, 'I.orderID = L.orderID', 'L')
            ->where(
               "$p_model.statusID = :paymentStatus: 
                AND $p_model.active = :paymentActive: 
                AND O.active = :orderActive: 
                AND O.userID = :userID:",
                [
                    'paymentStatus' => PaymentStatus::PAID,
                    'paymentActive' => self::ENABLED,
                    'orderActive'   => Order::ENABLED,
                    'userID'        => $user->id,
                ]
            )
            ->orderBy("$p_model.modifiedAt DESC, $p_model.id DESC")
            ->groupBy("$p_model.id, I.id, L.orderID");

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
     * Get all in payment
     * 
     * @param int $page Current page
     * 
     * @return Repository
     * 
     * @throws Exception
     */
    public static function getInPayment(int $page = 1): Repository
    {
        $user = User::getUser();

        if (!$user) {
            throw new \Exception('User not found!');
        }

        $p_model = self::class;

        $query = self::query()
            ->createBuilder()
            ->columns("
                $p_model.id as paymentID,
                $p_model.orderID AS orderID,
                $p_model.hashCode as hashCode,
                I.productID AS productID,
                I.quantity AS quantity,
                I.price AS price,
                SOffset.value - DATEDIFF(CURRENT_DATE(), P.createdAt) AS due_days,
                DATE_FORMAT(FROM_UNIXTIME(UNIX_TIMESTAMP(P.createdAt) + (SOffset.value * 86400)), '%d-%m-%Y') AS due_date,
                (SOffset.value + MaxDelay.value) - DATEDIFF(CURRENT_DATE(), P.createdAt) AS cancelation_days,
                DATE_FORMAT(FROM_UNIXTIME(UNIX_TIMESTAMP(P.createdAt)  + ((SOffset.value + MaxDelay.value) * 86400)), '%d-%m-%Y') AS cancelation_date
            ")
            ->innerJoin(Order::class, null, 'O')
            ->innerJoin(Item::class, 'O.id = I.orderID', 'I')
            ->innerJoin(Product::class, 'I.productID = P.id', 'P')
            ->innerJoin(ProductAttribute::class, 'P.id = Period.productID AND Period.name = "' . ProductAttribute::SUBSCRIPTION_PERIOD . '"', 'Period')
            ->innerJoin(ProductAttribute::class, 'P.id = MaxDelay.productID AND MaxDelay.name = "' . ProductAttribute::SUBSCRIPTION_MAX_DELAY . '"', 'MaxDelay')
            ->innerJoin(ProductAttribute::class, 'P.id = SOffset.productID AND SOffset.name = "' . ProductAttribute::SUBSCRIPTION_PAYMENT_OFFSET . '"', 'SOffset')
            ->where("
                $p_model.statusID = :paymentStatus: 
                AND $p_model.active = :paymentActive: 
                AND O.statusID = :orderStatus: 
                AND O.active = :orderActive: 
                AND O.userID = :userID: 
                AND I.active = :itemActive:
            ", [
                'paymentStatus' => PaymentStatus::CREATED,
                'paymentActive' => self::ENABLED,
                'orderStatus'   => OrderStatus::RENEWAL,
                'orderActive'   => Order::ENABLED,
                'userID'        => $user->id,
                'itemActive'    => Item::ENABLED,
            ])
            ->orderBy("$p_model.id DESC");


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
     * Generate invoice number
     * 
     * @param int $orderID Order 
     * @param int $padLen  Padding length
     * 
     * @return string
     */
    private static function generateInvoiceNumber(int $orderID, int $padLen = self::INVOICEPAD): string
    {
        return str_pad((string) $orderID, $padLen, '0', STR_PAD_LEFT);
    }
}
