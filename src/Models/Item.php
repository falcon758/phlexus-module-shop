<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Di;
use Phalcon\Mvc\Model;

/**
 * Class Item
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Item extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    /**
     * @var int
     */
    public $id;

    /**
     * @var int|null
     */
    public $active;

    /**
     * @var int
     */
    public int $productID;

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
        $this->setSource('items');

        $this->hasOne('productID', Product::class, 'id', [
            'alias'    => 'product',
            'reusable' => true,
        ]);

        $this->hasOne('orderID', Order::class, 'id', [
            'alias'    => 'order',
            'reusable' => true,
        ]);
    }

    /**
     * Has subscription product
     * 
     * @return bool
     */
    public function hasSubscriptionProduct(): bool
    {
        return $this->product->hasSubscription();
    }

    /**
     * Disable item
     *
     * @return bool
     */
    public function disableItem(): bool {
        $this->active = self::DISABLED;
        return $this->save();
    }

    /**
     * Create item
     * 
     * @param int $productID Product id to assign
     * @param int $orderID   Order id to assign
     *
     * @return Item
     * 
     * @throws Exception
     */
    public static function createItem(int $productID, int $orderID): Item {
        $item = new self;

        $product = Product::findFirstByid($productID);

        if (!$product) {
            throw new \Exception('Product doesn\'t exists');
        }

        $item->productID = $productID;
        $item->orderID   = $orderID;

        if (!$item->save()) {
            throw new \Exception('Unable to process item');
        }

        return $item;
    }

    /**
     * Disable item by order
     *
     * @param int $itemID    Item id to disable
     * @param int $orderID   Order id associated
     * 
     * @return bool
     */
    public static function disableOrderItem(int $itemID, int $orderID): bool {
        $disableItem = Di::getDefault()->getShared('db')->prepare('
            UPDATE items SET active = :active WHERE id = :id AND orderID = :orderID
        ');

        $active = self::DISABLED;

        $disableItem->bindParam(':active', $active);
        $disableItem->bindParam(':id', $itemID);
        $disableItem->bindParam(':orderID', $orderID);

        return $disableItem->execute();
    }
}
