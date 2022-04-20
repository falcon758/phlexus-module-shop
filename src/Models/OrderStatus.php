<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class OrderStatus
 *
 * @package Phlexus\Modules\Shop\Models
 */
class OrderStatus extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const CREATED = 1;

    public const RENEWAL = 2;

    public const DONE = 3;

    public const CANCELED = 4;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public string $status;

    /**
     * @var int|null
     */
    public $active;

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
        $this->setSource('order_status');
    }
}
