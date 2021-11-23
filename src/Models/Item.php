<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
use Phlexus\Modules\Shop\Models\Order;
use Phlexus\Modules\Shop\Models\Product;

/**
 * Class Product
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Product extends Model
{
    const DISABLED = 0;

    const ENABLED = 1;

    public $id;

    public $active;

    public $productId;

    public $orderId;

    public $createdAt;

    public $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('products');

        $this->hasOne('productId', Product::class, 'id', [
            'alias'    => 'Product',
            'reusable' => true,
        ]);

        $this->hasOne('orderId', Order::class, 'id', [
            'alias'    => 'Order',
            'reusable' => true,
        ]);
    }
}
