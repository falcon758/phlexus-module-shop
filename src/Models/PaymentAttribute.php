<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Models\Model;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;

/**
 * Class PaymentAttribute
 *
 * @package Phlexus\Modules\Shop\Models
 */
class PaymentAttribute extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const SUBSCRIPTION_DATE_LIMIT = 'subscription_date_limit';

    public const SUBSCRIPTION_MAX_DELAY = 'subscription_max_delay';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $value;

    /**
     * @var int|null
     */
    public $active;

    /**
     * @var int
     */
    public int $paymentID;

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
        $this->setSource('payment_attributes');

        $this->hasOne('paymentID', Payment::class, 'id', [
            'alias'    => 'payment',
            'reusable' => true,
        ]);
    }

    /**
     * Get Multiple Attributes
     * 
     * @param array $names Array of names to retrieve
     * 
     * @return array
     */
    public static function getAttributes(int $paymentID, array $names): array
    {
        $cNames = count($names);

        if ($cNames === 0) {
            return [];
        }

        $inQuery = '?' . implode(', ?', range(2, $cNames + 1));

        $values = array_merge([self::ACTIVE, $paymentID], $names);

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
    public static function setAttributes(int $paymentID, array $attributes): bool
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
                $attribute->paymentID = (int) $paymentID;

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
}
