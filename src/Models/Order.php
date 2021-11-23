<?php
declare(strict_types=1);

namespace Phlexus\Modules\Shop\Models;

use Phalcon\Mvc\Model;
use Phlexus\Modules\BaseUser\Models\User;
use Phlexus\Modules\Shop\Models\UserAddress;
use Phlexus\Modules\Shop\Models\PaymentMethod;
use Phlexus\Modules\Shop\Models\ShippingMethod;

/**
 * Class Product
 *
 * @package Phlexus\Modules\Shop\Models
 */
class Product extends Model
{
    const DISABLED = 0;

    const ENABLED = 1;

    public $id;

    public $active;

    public $userId;

    public $billingID;

    public $shipmentID;

    public $paymentMethodID;

    public $shippingMethodID;

    public $createdAt;

    public $modifiedAt;

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        $this->setSource('orders');

        $this->hasOne('userID', User::class, 'id', [
            'alias'    => 'user',
            'reusable' => true,
        ]);

        $this->hasOne('billingID', UserAddress::class, 'id', [
            'alias'    => 'billing_address',
            'reusable' => true,
        ]);

        $this->hasOne('shipmentID', UserAddress::class, 'id', [
            'alias'    => 'shipment_address',
            'reusable' => true,
        ]);

        $this->hasOne('paymentMethodID', PaymentMethod::class, 'id', [
            'alias'    => 'payment_method',
            'reusable' => true,
        ]);

        $this->hasOne('shippingMethodID', ShippingMethod::class, 'id', [
            'alias'    => 'shipping_method',
            'reusable' => true,
        ]);
    }
}
