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

    /**
     * @var int
     */
    public int $id;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $value;

    /**
     * @var int
     */
    public int $active;

    /**
     * @var int
     */
    public int $orderID;

    /**
     * @var string
     */
    public string $createdAt;

    /**
     * @var string
     */
    public string $modifiedAt;

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
