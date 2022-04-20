<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class ProductAttributes
 *
 * @package Phlexus\Modules\Shop\Models
 */
class ProductAttributes extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const SUBSCRIPTION_PERIOD = 'subscription_period';

    public const SUBSCRIPTION_PAYMENT_OFFSET = 'subscription_payment_offset';

    public const SUBSCRIPTION_MAX_DELAY = 'subscription_max_delay';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $value;

    /**
     * @var int|null
     */
    public $active;

    /**
     * @var int
     */
    public int $productID;

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
        $this->setSource('product_attributes');

        $this->hasOne('productID', Product::class, 'id', [
            'alias'    => 'product',
            'reusable' => true,
        ]);
    }
}
