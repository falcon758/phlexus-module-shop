<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class Locale
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Locale extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $active;

    /**
     * @var int
     */
    public $countryID;

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
     * @param int    $countryID Country id to assign
     *
     * @return Locale
     * 
     * @throws Exception
     */
    public static function createLocale(string $name, int $countryID): Locale {
        $name = trim($name);

        $locale = self::findFirst([
            'conditions' => 'active = :active: AND countryID = :country_id: AND name = :name:',
            'bind'       => [
                'active'     => self::ENABLED,
                'country_id' => $countryID,
                'name'       => $name,
            ],
        ]);

        if ($locale) {
            return $locale;
        }

        $locale = new self;
        $locale->name = $name;
        $locale->countryID = $countryID;

        if (preg_match('/^[a-zA-Z]+$/', $name) !== 1 || !$locale->save()) {
            throw new \Exception('Unable to process local');
        }

        return $locale;
    }
}
