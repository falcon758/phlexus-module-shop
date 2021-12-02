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
    const DISABLED = 0;

    const ENABLED = 1;

    const BILLING = 1;

    const SHIPPING = 1;

    public $id;

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
        $this->setSource('address_type');
    }
}
