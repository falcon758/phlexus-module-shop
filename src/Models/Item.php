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

    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $active;

    /**
     * @var int
     */
    public $productID;

    /**
     * @var int
     */
    public $orderID;

    /**
     * @var string
     */
    public $createdAt;

    /**
     * @var string
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
     * Create items
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
        $item->orderID = $orderID;

        if (!$item->save()) {
            throw new \Exception('Unable to process item');
        }

        return $item;
    }
}
