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

    /**
     * @var int
     */
    public int $id;

    /**
     * @var string
     */
    public string $iso;

    /**
     * @var string
     */
    public string $country;

    /**
     * @var int
     */
    public int $active;

    /**
     * @var string
     */
    public string $createdAt;

    /**
     * @var string
     */
    public string $modifiedAt;

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
