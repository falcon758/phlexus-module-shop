<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Models\Model;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;

/**
 * Class ProductAttribute
 *
 * @package Phlexus\Modules\Shop\Models
 */
class ProductAttribute extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const PRODUCT_STOCK = 'product_stock';

    public const PRODUCT_REFERENCE = 'product_reference';

    public const SUBSCRIPTION_PERIOD = 'subscription_period';

    public const SUBSCRIPTION_PAYMENT_OFFSET = 'subscription_payment_offset';

    public const SUBSCRIPTION_MAX_DELAY = 'subscription_max_delay';

    public const SUBSCRIPTION_PERIOD_DEFAULT = 30;

    public const SUBSCRIPTION_PAYMENT_OFFSET_DEFAULT = 5;

    public const SUBSCRIPTION_MAX_DELAY_DEFAULT = 10;

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
    public int $productID;

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
        $this->setSource('product_attributes');

        $this->hasOne('productID', Product::class, 'id', [
            'alias'    => 'product',
            'reusable' => true,
        ]);
    }

    /**
     * Get Subscription Attributes
     *
     * @return array
     */
    public static function getSubscriptionAttributes()
    {
        return [
            self::SUBSCRIPTION_PERIOD         => self::SUBSCRIPTION_PERIOD_DEFAULT,
            self::SUBSCRIPTION_PAYMENT_OFFSET => self::SUBSCRIPTION_PAYMENT_OFFSET_DEFAULT,
            self::SUBSCRIPTION_MAX_DELAY      => self::SUBSCRIPTION_MAX_DELAY_DEFAULT,
        ];
    }

    /**
     * Set Multiple Attributes
     * 
     * @param array $attributes Array of names to set
     * 
     * @return bool
     */
    public static function setAttributes(int $productID, array $attributes): bool
    {
        // Create a transaction manager
        $manager = new TxManager();

        // Request a transaction
        $transaction = $manager->get();
        
        try {
            foreach ($attributes as $key => $value) {
                $attribute = new self;
                $attribute->setTransaction($transaction);
                $attribute->name      = (string) $key;
                $attribute->value     = (string) $value;
                $attribute->productID = (int) $productID;

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
     * Get Attribute
     * 
     * @param string $name Name to retrieve
     * 
     * @return string|null
     */
    public static function getAttribute(int $productID, string $name): ?string
    {
        if ($name === "") {
            return null;
        }

        $attribute = self::findFirst(
            [
                'active = :ActiveAttribute: AND name = :AttributeName: AND productID = :ProductID:',
                'bind' => [
                    'ActiveAttribute' => self::ACTIVE,
                    'AttributeName'   => $name,
                    'ProductID'       => $productID
                ]
            ]
        );

        return $attributes->toArray();
    }

    /**
     * Get Multiple Attributes
     * 
     * @param array $names Array of names to retrieve
     * 
     * @return array
     */
    public static function getAttributes(int $productID, array $names): array
    {
        if (count($names) === 0) {
            return [];
        }

        $inQuery = '?' . implode(', ?', range(2, count($names)));

        $values = array_merge([self::ACTIVE, $productID], $names);

        $attributes = self::find(
            [
                'active = ?0 AND productID = ?1 AND name IN (' . $inQuery . ')',
                'bind' => $values
            ]
        );

        return $attributes->toArray();
    }
}
