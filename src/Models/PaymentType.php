<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

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
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public string $name;

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
        $this->setSource('payment_type');
    }
}
