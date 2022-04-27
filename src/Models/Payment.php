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
 * Class Payment
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Payment extends Model
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
     * @param int $paymentTypeID   Payment type id to assign
     * @param int $paymentMethodID Payment method id to assign
     * @param int $orderID         Order id to assign
     *
     * @return Payment
     * 
     * @throws Exception
     */
    public static function createPayment(int $paymentTypeID, int $paymentMethodID, int $orderID): Payment
    {
        $payment = new self;
        $payment->hashCode         = Di::getDefault()->getShared('security')->getRandom()->base64Safe(self::HASHLENGTH);
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

        $inQuery = '?' . implode(', ?', range(1, count($names)));

        $values = array_merge([$this->id], $names);

        $attributes = PaymentAttribute::find(
            [
                'paymentID = ?0 AND name IN (' . $inQuery . ')',
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
     * Get last paid by product
     * 
     * @param int $userID    User assigned to
     * @param int $productID Product to search for
     *
     * @return Payment|null
     */
    public static function getLastPaidByUserProduct(int $userID, int $productID)
    {
        $p_model = self::class;

        return self::query()
            ->columns($p_model . '.*')
            ->innerJoin(Order::class, null, 'O')
            ->innerJoin(Item::class, 'O.id = I.orderID', 'I')
            ->where(" $p_model.statusID = :status: AND O.userID = :userID: AND I.productID = :productID:", [
                'status'    => PaymentStatus::PAID,
                'userID'    => $userID,
                'productID' => $productID,
            ])
            ->orderBy($p_model . '.id DESC')
            ->execute()
            ->getFirst();
    }

    /**
     * Get all in payment
     * 
     * @return Simple
     * 
     * @throws Exception
     */
    public static function getInPayment()
    {
        $user = User::getUser();

        if (!$user) {
            throw new \Exception('User not found!');
        }

        $p_model = self::class;

        return self::query()
            ->columns($p_model . '.*')
            ->innerJoin(Order::class, null, 'O')
            ->where("
                $p_model.active = :paymentActive:
                $p_model.statusID = :paymentStatus:
                AND O.active = :orderActive:  
                AND O.statudID = :orderStatus: 
                O.userID = :userID:
            ", [
                'paymentActive' => self::ENABLED,
                'paymentStatus' => PaymentStatus::CREATED,
                'orderActive'   => Order::ENABLED,
                'orderStatus'   => OrderStatus::RENEWAL,
                'userID'        => $user->id,
            ])
            ->orderBy($p_model . '.id DESC')
            ->execute();
    }
}
