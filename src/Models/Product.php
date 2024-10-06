<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Libraries\Media\Models\Media;
use Phlexus\Models\Model;
use Phalcon\Mvc\Model\Resultset\Simple;
use Phalcon\Mvc\Model\Row;

/**
 * Class Product
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Product extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const HIDDEN = 0;

    public const VISIBLE = 1;

    /**
     * @var int
     */
    public ?int $id = null;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string|null
     */
    public ?string $description = null;

    /**
     * @var double
     */
    public string $price;

    /**
     * @var int
     */
    public ?int $isSubscription = null;

    /**
     * @var int|null
     */
    public ?int $active = null;

    /**
     * @var int|null
     */
    public ?int $visible = null;

    /**
     * @var int|null
     */
    public ?int $imageID = null;

    /**
     * @var int
     */
    public int $productTypeID;

    /**
     * @var int
     */
    public ?int $parentID = null;

    /**
     * @var string|null
     */
    public ?string $createdAt = null;

    /**
     * @var string|null
     */
    public ?string $modifiedAt = null;

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

        $this->hasOne('productTypeID', ProductType::class, 'id', [
            'alias'    => 'productType',
            'reusable' => true,
        ]);

        $this->hasOne('parentID', Product::class, 'id', [
            'alias'    => 'parentProduct',
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
     * Get Subscription Attributes
     *
     * @return array
     */
    public function getSubscriptionAttributes(): array
    {
        $productAttr = ProductAttribute::getAttributes(
            $this->id,
            [
                ProductAttribute::SUBSCRIPTION_PERIOD,
                ProductAttribute::SUBSCRIPTION_PAYMENT_OFFSET,
                ProductAttribute::SUBSCRIPTION_MAX_DELAY
            ]
        );
        
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
     * Get Available Products
     *
     * @return Simple
     */
    public static function getAvailableProducts(): Simple
    {
        $p_model = self::class;

        return self::query()
            ->leftJoin(ProductAttribute::class, "$p_model.id = Stock.productID AND Stock.name = '" . ProductAttribute::PRODUCT_STOCK . "'", 'Stock')
            ->where(
                "$p_model.active = :productActive:
                AND $p_model.visible = :productVisibility:
                AND (Stock.id IS NULL OR CAST(Stock.value AS SIGNED) > 0)",
                [
                    'productActive'     => self::ENABLED,
                    'productVisibility' => self::VISIBLE,
                ]
            )
            ->execute();
    }

    /**
     * Get Available Product
     *
     * @return Simple
     */
    public static function getAvailableProduct(int $productID, int $quantity = 1): ?Product
    {
        $p_model = self::class;

        return self::query()
            ->leftJoin(ProductAttribute::class, "$p_model.id = Stock.productID AND Stock.name = '" . ProductAttribute::PRODUCT_STOCK . "'", 'Stock')
            ->where(
                "$p_model.active = :productActive:
                AND $p_model.visible = :productVisibility:
                AND $p_model.id = :productID:
                AND (Stock.id IS NULL OR CAST(Stock.value AS SIGNED) >= :productQuantity:)",
                [
                    'productActive'     => self::ENABLED,
                    'productVisibility' => self::VISIBLE,
                    'productID'         => $productID,
                    'productQuantity'   => $quantity,
                ]
            )
            ->execute()
            ->getFirst();
    }

    /**
     * Change Product Stock
     *
     * @return bool
     */
    public static function changeStock(int $productID, int $quantity = 1): bool
    {
        $attribute = ProductAttribute::getAttribute($productID, ProductAttribute::PRODUCT_STOCK);
        
        if (!$attribute) {
            return false;
        }

        // @TODO: change this to disallow negative values on stock
        $attribute->value = $attribute->value + $quantity;

        return $attribute->save();
    }

    /**
     * Increase Product Stock
     *
     * @return bool
     */
    public static function increaseStock(int $productID, int $quantity = 1): bool
    {
        return self::changeStock($productID, $quantity);
    }

    /**
     * Decrease Product Stock
     *
     * @return bool
     */
    public static function decreaseStock(int $productID, int $quantity = 1): bool
    {
        return self::changeStock($productID, -1 * $quantity);
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
                AND p_model.active = :productActive:
                AND $p_model.userID = :userID:
                AND $p_model.id = :productID:
                AND $p_model.isSubscription = :isSubscription:
                AND DATEDIFF(CURRENT_DATE(), PST.createdAt) <= Period.value + MaxDelay.value
                AND PSD.id IS NULL",
                [
                    'orderActive'    => Order::ENABLED,
                    'orderStatus'    => OrderStatus::RENEWAL,
                    'itemActive'     => Item::ENABLED,
                    'itemActive'     => self::ENABLED,
                    'userID'         => $userID,
                    'productID'      => $productID,
                    'isSubscription' => 1,
                ]
            )
            ->execute()
            ->count() === 1;
    }
}
