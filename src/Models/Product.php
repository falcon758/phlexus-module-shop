<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Libraries\Media\Models\Media;
use Phalcon\Mvc\Model;

/**
 * Class Product
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Product extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

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
    public string $price;

    /**
     * @var int
     */
    public $isSubscription;

    /**
     * @var int|null
     */
    public $active;

    /**
     * @var int|null
     */
    public $imageID;

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
        $this->setSource('products');
        
        $this->hasOne('imageID', Media::class, 'id', [
            'alias'    => 'media',
            'reusable' => true,
        ]);

        $this->hasMany('id', ProductAttributes::class, 'productID', ['alias' => 'productAttributes']);
    }

    /**
     * Has subscription
     * 
     * @return bool
     */
    public function hasSubscription(): bool
    {
        return ((int) $this->isSubscription) === 1;
    }

    /**
     * Get Multiple Attributes
     * 
     * @param array $names Array of names to retrieve
     * 
     * @return array
     */
    public function getAttributes(array $names): array
    {
        if (count($names) === 0) {
            return [];
        }

        $inQuery = '?' . implode(', ?', range(1, count($names)));

        $values = array_merge([$this->id], $names);

        $attributes = ProductAttributes::find(
            [
                'productID = ?0 AND name IN (' . $inQuery . ')',
                'bind' => $values
            ]
        );

        return $attributes->toArray();
    }

    /**
     * Get Subscription Attributes
     *
     * @return array
     */
    public function getSubscriptionAttributes(): array
    {
        $productAttr = $this->getAttributes([
            ProductAttributes::SUBSCRIPTION_PERIOD,
            ProductAttributes::SUBSCRIPTION_PAYMENT_OFFSET,
            ProductAttributes::SUBSCRIPTION_MAX_DELAY
        ]);
        
        if (count($productAttr) === 0) {
            return false;
        }

        $subscriptionAttr = [
            'period'    => 0,
            'offset'    => 0,
            'max_delay' => 0
        ];

        foreach ($productAttr as $attr) {
            switch ($attr['name']) {
                case ProductAttributes::SUBSCRIPTION_PERIOD:
                    $subscriptionAttr['period'] = $attr['value'];
                    break;
                case ProductAttributes::SUBSCRIPTION_PAYMENT_OFFSET:
                    $subscriptionAttr['offset'] = $attr['value'];
                    break;
                case ProductAttributes::SUBSCRIPTION_MAX_DELAY:
                    $subscriptionAttr['max_delay'] = $attr['value'];
                    break;
                default:
                    throw new \Exception('Attribute not implemented');
                    break;
            }
        }

        return $subscriptionAttr;
    }

    /**
     * Set Multiple Attributes
     * 
     * @param array $attributes Array of names to set
     * 
     * @return bool
     */
    public function setAttributes(array $attributes): bool
    {
        foreach ($attributes as $key => $value) {
            $attribute          = new ProductAttributes();
            $attribute->name    = $key;
            $attribute->value   = $value;
            $attribute->paymentID = (int) $this->id;

            if (!$attribute->save()) {
                return false;
            }
        }

        return true;
    }
}
