<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class ShippingMethod
 *
 * @package Phlexus\Modules\Shop\Models
 */
class ShippingMethod extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public $id;

    public $name;

    public $active;

    public $postCodeID;

    public $createdAt;

    public $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('shipping_method');
    }
}
