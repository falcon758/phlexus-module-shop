<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Models\Model;

/**
 * Class ProductAttribute
 *
 * @package Phlexus\Modules\Shop\Models
 */
class ProductAttribute extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const SUBSCRIPTION_PERIOD = 'subscription_period';

    public const SUBSCRIPTION_PAYMENT_OFFSET = 'subscription_payment_offset';

    public const SUBSCRIPTION_MAX_DELAY = 'subscription_max_delay';

    public const SUBSCRIPTION_PERIOD_DEFAULT = 30;

    public const SUBSCRIPTION_PAYMENT_OFFSET_DEFAULT = 5;

    public const SUBSCRIPTION_MAX_DELAY_DEFAULT = 10;

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

    /**
     * Get Subscription Attributes
     *
     * @return array
     */
    public static function getSubscriptionAttributes()
    {
        return [
            self::SUBSCRIPTION_PERIOD         => self::SUBSCRIPTION_PERIOD_DEFAULT,
            self::SUBSCRIPTION_PAYMENT_OFFSET => self::SUBSCRIPTION_PAYMENT_OFFSET_DEFAULT,
            self::SUBSCRIPTION_MAX_DELAY      => self::SUBSCRIPTION_MAX_DELAY_DEFAULT,
        ];
    }
}
