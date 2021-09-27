<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Security;
use Phlexus\Modules\BaseUser\Models\Users;

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

    public $userId;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->hasOne('userID', Users::class, 'id', [
            'alias'    => 'user',
            'reusable' => true,
        ]);
    }
}
