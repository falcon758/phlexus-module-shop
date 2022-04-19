<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class PaymentAttributes
 *
 * @package Phlexus\Modules\Shop\Models
 */
class PaymentAttributes extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

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
