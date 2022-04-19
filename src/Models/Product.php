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
    public int $isSubscription;

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
}
