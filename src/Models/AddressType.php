<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class AddressType
 *
 * @package Phlexus\Modules\Shop\Models
 */
class AddressType extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const BILLING = 1;

    public const SHIPPING = 2; 

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $address_type;

    /**
     * @var int
     */
    public $active;

    /**
     * @var int
     */
    public $postCodeID;

    /**
     * @var string
     */
    public $createdAt;

    /**
     * @var string
     */
    public $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('address_type');
    }
}
