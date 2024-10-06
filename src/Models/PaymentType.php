<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Models\Model;

/**
 * Class PaymentType
 *
 * @package Phlexus\Modules\Shop\Models
 */
class PaymentType extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const PAYMENT = 1;

    public const RENEWAL = 2;

    /**
     * @var int|null
     */
    public ?int $id = null;

    /**
     * @var string
     */
    public string $name;

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
        $this->setSource('payment_type');
    }
}
