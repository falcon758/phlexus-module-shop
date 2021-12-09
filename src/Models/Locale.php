<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
use Phlexus\Modules\Shop\Models\Country;

/**
 * Class Locale
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Locale extends Model
{
    const DISABLED = 0;

    const ENABLED = 1;

    public $id;

    public $name;

    public $active;

    public $countryID;

    public $createdAt;

    public $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('locale');

        $this->hasOne('countryID', Country::class, 'id', [
            'alias'    => 'Country',
            'reusable' => true,
        ]);
    }

    /**
     * Create locale or return if exists
     * 
     * @param string $name      Locale to create
     * @param int    $countryId Country id to assign
     *
     * @return Locale
     * 
     * @throws Exception
     */
    public static function createLocale(string $name, int $countryId): Locale {
        $locale = self::findFirst([
            'conditions' => 'active = :active: AND countryID = :country_id: AND name = :name:',
            'bind'       => [
                'active'     => self::ENABLED,
                'country_id' => $countryId,
                'name'       => $name,
            ],
        ]);

        if ($locale) {
            return $locale;
        }

        $locale = new self;
        $locale->name = $name;
        $locale->countryID = $countryId;

        if (preg_match('/^[a-zA-Z]+$/', $name) !== 1 || !$locale->save()) {
            throw new \Exception('Unable to process local');
        }

        return $locale;
    }
}
