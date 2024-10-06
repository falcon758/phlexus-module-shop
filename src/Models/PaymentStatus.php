<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Models\Model;

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
     * @var int|null
     */
    public ?int $id = null;

    /**
     * @var string
     */
    public string $status;

    /**
     * @var int|null
     */
    public ?int $active = null;

    /**
     * @var string|null
     */
    public ?string $createdAt = null;

    /**
     * @var string|null
     */
    public ?string $modifiedAt = null;

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
