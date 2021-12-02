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

    /**
     * Create user address or return if exists
     * 
     * @param int $userId        User to assign address to
     * @param int $addressId     Address id to assign
     * @param int $addressTypeId Address type id to assign
     *
     * @return UserAddress
     */
    public static function createUserAddress(int $userId, int $addressId, int $addressTypeId): UserAddress {
        $userAddress = self::findFirst(
            [
                'conditions' => 'status = :status: AND addressId = :address_id: 
                                AND addressTypeId = :address_type_id:',
                'bind'       => [
                    'status'          => UserAddress::ENABLED,
                    'address_id'      => $addressId,
                    'address_type_id' => $addressTypeId
                ],
            ]
        );

        if ($userAddress) {
            return $userAddress;
        }

        $userAddress                = new self;
        $userAddress->userID        = $userId;
        $userAddress->addressID     = $addressId;
        $userAddress->addressTypeId = $addressTypeId;

        return $userAddress->save() ? $userAddress : null;
    }
}
