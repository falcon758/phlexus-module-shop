<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
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
    public string $hashCode;

    /**
     * @var int|null
     */
    public $status;

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

        $this->hasMany('id', PaymentAttributes::class, 'paymentID', ['alias' => 'paymentAttributes']);
    }

    /**
     * Cancel payment
     * 
     * @return bool
     */
    public function cancelPayment(): bool
    {
        $this->status = self::CANCELED;
        return $this->save();
    }

    /**
     * Set as paid
     * 
     * @return bool
     */
    public function paid(): bool
    {
        $this->status = self::PAID;
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
        return $this->status === self::PAID;
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
        $payment->status           = self::CREATED;

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

        $attributes = PaymentAttributes::find(
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
        foreach ($attributes as $key => $value) {
            $attribute          = new PaymentAttributes();
            $attribute->name    = $key;
            $attribute->value   = $value;
            $attribute->paymentID = (int) $this->id;
            if (!$attribute->save()) {
                return false;
            }
        }

        return true;
    }
}
