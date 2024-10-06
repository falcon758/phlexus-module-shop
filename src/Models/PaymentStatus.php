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
    public ?int $id;

    /**
     * @var string
     */
    public string $status;

    /**
     * @var int|null
     */
    public ?int $active;

    /**
     * @var string|null
     */
    public ?string $createdAt;

    /**
     * @var string|null
     */
    public ?string $modifiedAt;

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
