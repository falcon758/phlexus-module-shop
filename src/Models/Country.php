<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class Country
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Country extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public $id;

    public $iso;

    public $country;

    public $active;

    public $createdAt;

    public $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('countries');
    }
}
