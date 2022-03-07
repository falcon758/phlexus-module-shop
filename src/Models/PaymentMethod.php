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
    public int $id;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var int
     */
    public int $active;

    /**
     * @var int
     */
    public int $postCodeID;

    /**
     * @var string
     */
    public string $createdAt;

    /**
     * @var string
     */
    public string $modifiedAt;

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
