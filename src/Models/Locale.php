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
}
