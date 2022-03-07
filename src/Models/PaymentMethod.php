<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;

/**
 * Class PaymentMethod
 *
 * @package Phlexus\Modules\Shop\Models
 */
class PaymentMethod extends Model
{
    public const DISABLED = 0;

    public const ENABLED = 1;

    public const PAYPAL = 1;

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
     * @var int
     */
    public int $postCodeID;

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
        $this->setSource('payment_method');
    }
}
