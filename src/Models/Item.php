<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

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

    public $id;

    public $active;

    public $productID;

    public $orderID;

    public $createdAt;

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
            'alias'    => 'Product',
            'reusable' => true,
        ]);

        $this->hasOne('orderID', Order::class, 'id', [
            'alias'    => 'Order',
            'reusable' => true,
        ]);
    }

    /**
     * Create items
     * 
     * @param int $productId Product id to assign
     * @param int $orderId   Order id to assign
     *
     * @return Item
     * 
     * @throws Exception
     */
    public static function createItem(int $productId, int $orderId): Item {
        $item = new self;

        $product = Product::findFirstByid($productId);

        if (!$product) {
            throw new \Exception('Product doesn\'t exists');
        }

        $item->productID = $productId;
        $item->orderID = $orderId;

        if (!$item->save()) {
            throw new \Exception('Unable to process item');
        }

        return $item;
    }
}
