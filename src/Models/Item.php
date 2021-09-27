<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Security;
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

    public $productID;

    public $orderID;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->hasOne('orderID', Product::class, 'id', [
            'alias'    => 'Product',
            'reusable' => true,
        ]);

        $this->hasOne('orderID', Order::class, 'id', [
            'alias'    => 'Order',
            'reusable' => true,
        ]);
    }
}
