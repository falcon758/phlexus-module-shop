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
    public $name;

    /**
     * @var int
     */
    public $active;

    /**
     * @var int
     */
    public $postCodeID;

    /**
     * @var string
     */
    public $createdAt;

    /**
     * @var string
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
