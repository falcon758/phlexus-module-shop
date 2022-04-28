<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phlexus\Models\Model;

/**
 * Class PaymentAttribute
 *
 * @package Phlexus\Modules\Shop\Models
 */
class PaymentAttribute extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const SUBSCRIPTION_DATE_LIMIT = 'subscription_date_limit';

    public const SUBSCRIPTION_MAX_DELAY = 'subscription_max_delay';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $value;

    /**
     * @var int|null
     */
    public $active;

    /**
     * @var int
     */
    public int $paymentID;

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
        $this->setSource('payment_attributes');

        $this->hasOne('paymentID', Payment::class, 'id', [
            'alias'    => 'payment',
            'reusable' => true,
        ]);
    }
}
