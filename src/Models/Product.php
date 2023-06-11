<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Libraries\Media\Models\Media;
use Phlexus\Models\Model;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;

/**
 * Class Product
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Product extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string|null
     */
    public $description;

    /**
     * @var double
     */
    public string $price;

    /**
     * @var int
     */
    public $isSubscription;

    /**
     * @var int|null
     */
    public $active;

    /**
     * @var int|null
     */
    public $imageID;

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
        $this->setSource('products');
        
        $this->hasOne('imageID', Media::class, 'id', [
            'alias'    => 'media',
            'reusable' => true,
        ]);

        $this->hasMany('id', ProductAttribute::class, 'productID', ['alias' => 'productAttribute']);
    }

    /**
     * Has subscription
     * 
     * @return bool
     */
    public function hasSubscription(): bool
    {
        return ((int) $this->isSubscription) === 1;
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

        $attributes = ProductAttribute::find(
            [
                'active = ?0 AND productID = ?1 AND name IN (' . $inQuery . ')',
                'bind' => $values
            ]
        );

        return $attributes->toArray();
    }

    /**
     * Get Subscription Attributes
     *
     * @return array
     */
    public function getSubscriptionAttributes(): array
    {
        $productAttr = $this->getAttributes([
            ProductAttribute::SUBSCRIPTION_PERIOD,
            ProductAttribute::SUBSCRIPTION_PAYMENT_OFFSET,
            ProductAttribute::SUBSCRIPTION_MAX_DELAY
        ]);
        
        if (count($productAttr) === 0) {
            return false;
        }

        $subscriptionAttr = [
            'period'    => 0,
            'offset'    => 0,
            'max_delay' => 0
        ];

        foreach ($productAttr as $attr) {
            switch ($attr['name']) {
                case ProductAttribute::SUBSCRIPTION_PERIOD:
                    $subscriptionAttr['period'] = $attr['value'];
                    break;
                case ProductAttribute::SUBSCRIPTION_PAYMENT_OFFSET:
                    $subscriptionAttr['offset'] = $attr['value'];
                    break;
                case ProductAttribute::SUBSCRIPTION_MAX_DELAY:
                    $subscriptionAttr['max_delay'] = $attr['value'];
                    break;
                default:
                    throw new \Exception('Attribute not implemented');
                    break;
            }
        }

        return $subscriptionAttr;
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
                $attribute = new ProductAttribute();
                $attribute->setTransaction($transaction);
                $attribute->name      = (string) $key;
                $attribute->value     = (string) $value;
                $attribute->productID = (int) $this->id;

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
     * Has subscription active
     * 
     * @param int $productID Product to search for
     * @param int $userID    User to check against
     * 
     * @return bool
     * 
     * @throws Exception
     */
    public static function hasSubscriptionActive(int $productID, int $userID): bool
    {
        $p_model = self::class;

        return self::query()
            ->innerJoin(ProductAttribute::class, "$p_model.id = Period.productID AND Period.name = '" . ProductAttribute::SUBSCRIPTION_PERIOD . "'", 'Period')
            ->innerJoin(ProductAttribute::class, "$p_model.id = MaxDelay.productID AND MaxDelay.name = '" . ProductAttribute::SUBSCRIPTION_MAX_DELAY . "'", 'MaxDelay')
            ->innerJoin(Item::class, "$p_model.id = I.productID", 'I')
            ->innerJoin(Order::class, "I.orderID = O.id", 'O')
            ->innerJoin(Payment::class, "O.id = PST.orderID", 'PST')
            ->leftJoin(Payment::class, 
                    "O.id = PSD.orderID
                AND 
                    (
                            PST.createdAt < PSD.createdAt 
                        OR (
                            PST.createdAt = PSD.createdAt AND PST.id < PSD.id
                        )
                    )
                
                AND
                    PST.active = PSD.active
                AND
                    PST.statusID = PSD.statusID
                AND
                    PST.paymentTypeID = PSD.paymentTypeID",
                'PSD'
            )
            ->where(
                "O.active = :orderActive: 
                AND O.statusID = :orderStatus: 
                AND I.active = :itemActive: 
                AND $p_model.userID = :userID:
                AND $p_model.id = :productID:
                AND $p_model.isSubscription = :isSubscription: 
                AND DATEDIFF(CURRENT_DATE(), PST.createdAt) <= Period.value + MaxDelay.value
                AND PSD.id IS NULL",
                [
                    'orderActive'    => Order::ENABLED,
                    'orderStatus'    => OrderStatus::RENEWAL,
                    'itemActive'     => Item::ENABLED,
                    'userID'         => $userID,
                    'productID'      => $productID,
                    'isSubscription' => 1
                ]
            )
            ->execute()
            ->count() === 1;
    }
}
