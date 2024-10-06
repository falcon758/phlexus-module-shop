<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Di\Di;
use Phlexus\Models\Model;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;
use Exception;

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
     * @var int|null
     */
    public ?int $id = null;

    /**
     * @var int
     */
    public int $quantity;

    /**
     * @var float
     */
    public float $price;

    /**
     * @var int|null
     */
    public ?int $active = null;

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
    public function disableItem(): bool
    {
        $this->active = self::DISABLED;
        return $this->save();
    }

    /**
     * Create item
     * 
     * @param int    $productID Product id to assign
     * @param int    $orderID   Order id to assign
     * @param int    $quantity  Product quantity to assign
     * @param float  $price     Product price to assign
     *
     * @return Item
     * 
     * @throws Exception
     */
    public static function createItem(int $productID, int $orderID, int $quantity, float $price): Item
    {
        $item = new self;

        $product = self::getProductOrFail($productID);

        $item->productID = $productID;
        $item->orderID   = $orderID;
        $item->quantity  = $quantity;
        $item->price     = $price;

        if (!$item->save()) {
            throw new Exception('Unable to process item');
        }

        return $item;
    }

    /**
     * Create items
     * 
     * @param array $products Products and quantities to assign
     *
     * @return bool
     */
    public static function createItems(int $orderID, array $products): bool
    {
        // Create a transaction manager
        $manager = new TxManager();

        // Request a transaction
        $transaction = $manager->get();

        try {
            foreach ($products as $prodID => $quantity) {
                $item = new Item();
                $item->setTransaction($transaction);

                $productID       = (int) $prodID;
                $productQuantity = (int) $quantity;

                $product = self::getProductOrFail($productID, $productQuantity);

                $item->quantity  = $productQuantity;
                $item->price     = (float) $product->price;
                $item->productID = $productID;
                $item->orderID   = (int) $orderID;

                if (!$item->save()) {
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
     * Disable item by order
     *
     * @param int $itemID    Item id to disable
     * @param int $orderID   Order id associated
     * 
     * @return bool
     */
    public static function disableOrderItem(int $itemID, int $orderID): bool
    {
        $disableItem = Di::getDefault()->getShared('db')->prepare('
            UPDATE items SET active = :active WHERE id = :id AND orderID = :orderID
        ');

        $active = self::DISABLED;

        $disableItem->bindParam(':active', $active);
        $disableItem->bindParam(':id', $itemID);
        $disableItem->bindParam(':orderID', $orderID);

        return $disableItem->execute();
    }

    public static function getProductOrFail(int $productID, int $quantity = 1)
    {
        $product = Product::getAvailableProduct($productID, $quantity);

        if (!$product) {
            throw new Exception('Product not available');
        }

        return $product;
    }
}
