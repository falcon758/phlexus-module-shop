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
     * @var int
     */
    public $id;

    /**
     * @var double
     */
    public string $tax;

    /**
     * @var int|null
     */
    public $active;

    /**
     * @var int
     */
    public int $countryID;

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
