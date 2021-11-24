<?php

/**
 * This file is part of the Phlexus CMS.
 *
 * (c) Phlexus CMS <cms@phlexus.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Form;

use Phlexus\Forms\CaptchaForm;
use Phlexus\Modules\Shop\Models\Country;
use Phlexus\Modules\Shop\Models\PaymentMethod;
use Phlexus\Modules\Shop\Models\ShippingMethod;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Select;
use Phalcon\Validation\Validator\PresenceOf;

class checkoutForm extends CaptchaForm
{
    /**
     * Initialize form
     */
    public function initialize()
    {        
        $address = new Text('address', [
            'required' => true,
            'class' => 'form-control',
            'placeholder' => 'Address'
        ]);
        
        $address->addValidator(new PresenceOf(['message' => 'Address is required']));

        $post_code = new Text('post_code', [
            'required' => true,
            'class' => 'form-control',
            'placeholder' => 'Post Code'
        ]);

        $country = new Select(
            'country',
            Country::find(),
            [
                'using' => ['id', 'country'],
                'required' => true,
                'class' => 'form-control',
                'placeholder' => 'Country'
            ]
        );

        $payment_method = new Select(
            'payment_method',
            PaymentMethod::find(),
            [
                'using' => ['id', 'name'],
                'required' => true,
                'class' => 'form-control',
                'placeholder' => 'Payment method'
            ]
        );

        $shipping_method = new Select(
            'shipping_method',
            ShippingMethod::find(),
            [
                'using' => ['id', 'name'],
                'required' => true,
                'class' => 'form-control',
                'placeholder' => 'Shipping method'
            ]
        );

        $this->add($address);
        $this->add($post_code);
        $this->add($country);
        $this->add($payment_method);
        $this->add($shipping_method);
    }
}
