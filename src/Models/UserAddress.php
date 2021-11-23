<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
use Phlexus\Modules\BaseUser\Models\User;
use Phlexus\Modules\Shop\Models\Address;
use Phlexus\Modules\Shop\Models\AddressType;

/**
 * Class Address
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Address extends Model
{
    const DISABLED = 0;

    const ENABLED = 1;

    public $id;

    public $active;

    public $userID;

    public $addressID;

    public $addressTypeID;

    public $createdAt;

    public $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('user_address');

        $this->hasOne('userID', User::class, 'id', [
            'alias'    => 'user',
            'reusable' => true,
        ]);

        $this->hasOne('addressID', Address::class, 'id', [
            'alias'    => 'address',
            'reusable' => true,
        ]);

        $this->hasOne('addressTypeID', AddressType::class, 'id', [
            'alias'    => 'address_type',
            'reusable' => true,
        ]);
    }
}
