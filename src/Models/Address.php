<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Models\Model;

/**
 * Class Address
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Address extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    /**
     * @var int|null
     */
    public ?int $id = null;

    /**
     * @var string
     */
    public string $address;

    /**
     * @var int|null
     */
    public ?int $active = null;

    /**
     * @var int
     */
    public int $postCodeID;

    /**
     * @var string|null
     */
    public ?string $createdAt = null;

    /**
     * @var string|null
     */
    public ?string $modifiedAt = null;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('address');

        $this->hasOne('postCodeID', PostCode::class, 'id', [
            'alias'    => 'postCode',
            'reusable' => true,
        ]);
    }

    /**
     * Get encrypt fields
     * 
     * @return array Fields
     */
    public static function getEncryptFields() : array
    {
        return ['address'];
    }

    /**
     * Create address or return if exists
     * 
     * @param string $address    Address to create
     * @param string $postCode   Post code to create
     * @param string $locale     Locale to create
     * @param int    $countryID  Locale to verify
     *
     * @return Address
     * 
     * @throws Exception
     */
    public static function createAddress(
        string $address, string $postCode,
        string $locale, int $countryID
    ): Address {
        $newLocale = Locale::createLocale($locale, $countryID);

        $newPostCode = PostCode::createPostCode($postCode, (int) $newLocale->id);

        $address = trim($address);

        if (preg_match('/^[a-zA-Z0-9\s.-]*$/', $address) !== 1) {
            throw new \Exception('Unable to process address');
        }

        $newAddress = self::findFirst([
            'conditions' => 'active = :active: AND postCodeID = :postCodeID: AND address = :address:',
            'bind'       => [
                'active'    => self::ENABLED,
                'postCodeID' => $newPostCode->id,
                'address' => self::encrypt($address),
            ],
        ]);

        if ($newAddress) {
            return $newAddress;
        }

        $newAddress             = new self;
        $newAddress->address    = $address;
        $newAddress->postCodeID = (int) $newPostCode->id;

        if (!$newAddress->save()) {
            throw new \Exception('Unable to process address');
        }
        
        return $newAddress;
    }
}
