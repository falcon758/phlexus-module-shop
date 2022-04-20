<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class PaymentStatus
 *
 * @package Phlexus\Modules\Shop\Models
 */
class PaymentStatus extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const CREATED = 1;

    public const PAID = 2;

    public const CANCELED = 3;

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
        $this->setSource('payment_status');
    }
}
