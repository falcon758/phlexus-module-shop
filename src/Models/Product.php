<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Security;
use Phlexus\Modules\BaseUser\Models\User;

/**
 * Class Product
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Product extends Model
{
    const DISABLED = 0;

    const ENABLED = 1;

    public $id;

    public $name;

    public $active;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('products');
    }
}
