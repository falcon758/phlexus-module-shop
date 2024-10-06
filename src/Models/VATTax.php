<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class VATTax
 *
 * @package Phlexus\Modules\Shop\Models
 */
class VATTax extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    /**
     * @var int|null
     */
    public ?int $id = null;

    /**
     * @var double
     */
    public string $tax;

    /**
     * @var int|null
     */
    public ?int $active = null;

    /**
     * @var int
     */
    public int $countryID;

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
        $this->setSource('vat_tax');

        $this->hasOne('countryID', Country::class, 'id', [
            'alias'    => 'country',
            'reusable' => true,
        ]);
    }

    /**
     * Get tax vat by country
     * 
     * @param int $countryID Country id to assign
     *
     * @return double|null
     */
    public static function getVatByCountry(int $countryID): ?double {
        $vat = self::findFirst([
            'conditions' => 'active = :active: AND countryID = :country_id:',
            'bind'       => [
                'active'     => self::ENABLED,
                'country_id' => $countryID,
            ],
        ]);

        return $vat ? $vat->tax : null;
    }
}
