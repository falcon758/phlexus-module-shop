<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Models\Model;
use Phlexus\Modules\BaseUser\Models\User;
use Phalcon\Mvc\Model\Resultset\Simple;

/**
 * Class UserAddress
 *
 * @package Phlexus\Modules\Shop\Models
 */
class UserAddress extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    /**
     * @var int
     */
    public $id;

    /**
     * @var int|null
     */
    public $active;

    /**
     * @var int
     */
    public int $userID;

    /**
     * @var int
     */
    public int $addressID;

    /**
     * @var int
     */
    public int $addressTypeID;

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
            'alias'    => 'addressType',
            'reusable' => true,
        ]);
    }

    /**
     * Create user address or return if exists
     * 
     * @param int $userID        User to assign address to
     * @param int $addressID     Address id to assign
     * @param int $addressTypeID Address type id to assign
     *
     * @return UserAddress
     */
    public static function createUserAddress(int $userID, int $addressID, int $addressTypeID): UserAddress {
        $newUserAddress = self::findFirst(
            [
                'conditions' => 'active = :active: AND addressID = :addressID: 
                                AND addressTypeID = :addressTypeID:',
                'bind'       => [
                    'active'        => UserAddress::ENABLED,
                    'addressID'     => $addressID,
                    'addressTypeID' => $addressTypeID
                ],
            ]
        );

        if ($newUserAddress) {
            return $newUserAddress;
        }

        $newUserAddress                = new self;
        $newUserAddress->userID        = $userID;
        $newUserAddress->addressID     = $addressID;
        $newUserAddress->addressTypeID = $addressTypeID;

        if (!$newUserAddress->save()) {
            throw new \Exception('Unable to process user address');
        }

        return $newUserAddress;
    }

    /**
     * Get user address
     * 
     * @param int   $userID
     * @param array $addressTypeID
     *
     * @return Simple
     */
    public static function getUserAddress(int $userID, array $addressTypeID = [AddressType::SHIPPING]): ?Simple
    {
        $p_model = self::class;

        return self::query()
            ->columns("
                $p_model.addressTypeID AS addressTypeID,
                AD.address AS address,
                PC.postCode AS postCode,
                CT.country AS country
            ")
            ->innerJoin(Address::class, null, 'AD')
            ->innerJoin(PostCode::class, 'AD.postCodeID = PC.id', 'PC')
            ->innerJoin(Locale::class, 'PC.localeID = LC.id', 'LC')
            ->innerJoin(Country::class, 'LC.countryID = CT.id', 'CT')
            ->where(
                "$p_model.userID = :userID:
                AND $p_model.addressTypeID IN (:addressTypeFST:, :addressTypeSCD:)
                AND $p_model.active = :userAddressActive: 
                AND AD.active = :addressActive:",
                [
                    'userID'            => $userID,
                    'addressTypeFST'    => $addressTypeID[0] ?? null,
                    'addressTypeSCD'    => $addressTypeID[1] ?? null,
                    'userAddressActive' => self::ENABLED,
                    'addressActive'     => Address::ENABLED,
                ]
            )
            ->execute();
    }
}
