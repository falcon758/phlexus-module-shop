<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class OrderAttributes
 *
 * @package Phlexus\Modules\Shop\Models
 */
class OrderAttributes extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public $id;

    public $name;

    public $value;

    public $active;

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
        $this->setSource('order_attributes');

        $this->hasOne('orderID', Order::class, 'id', [
            'alias'    => 'order',
            'reusable' => true,
        ]);
    }
}
