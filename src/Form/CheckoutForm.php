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
use Phalcon\Validation\Validator\InclusionIn;
use Phalcon\Validation\Validator\Regex;

class checkoutForm extends CaptchaForm
{
    /**
     * Initialize form
     */
    public function initialize()
    {
        // Fields
        $address = new Text('address', [
            'required' => true,
            'class' => 'form-control',
            'placeholder' => 'Address'
        ]);

        $post_code = new Text('post_code', [
            'required' => true,
            'class' => 'form-control',
            'placeholder' => 'Post Code'
        ]);

        $country_data = Country::find();
        $country = new Select(
            'country',
            $country_data,
            [
                'using' => ['id', 'country'],
                'required' => true,
                'class' => 'form-control',
                'placeholder' => 'Country',
            ]
        );

        $payment_method_data = PaymentMethod::find();
        $payment_method = new Select(
            'payment_method',
            $payment_method_data,
            [
                'using' => ['id', 'name'],
                'required' => true,
                'class' => 'form-control',
                'placeholder' => 'Payment method',
            ]
        );

        $shipping_method_data = ShippingMethod::find();
        $shipping_method = new Select(
            'shipping_method',
            $shipping_method_data,
            [
                'using' => ['id', 'name'],
                'required' => true,
                'class' => 'form-control',
                'placeholder' => 'Shipping method',
            ]
        );

        // Validators
        $address->addValidators([
            new PresenceOf(['message' => 'Address is required']),
            new Regex(
                [
                    'pattern' => '/^[a-zA-Z0-9\s.-]*$/',
                    'message' => 'Address has invalid characters',
                ]
            )
        ]);

        $post_code->addValidators([
            new PresenceOf(['message' => 'Post Code is required']),
            new Regex(
                [
                    'pattern' => '/^[0-9]+-[0-9]+$/',
                    'message' => 'Post Code has invalid characters',
                ]
            )
        ]);

        $country->addValidators([
            new PresenceOf(['message' => 'Country is required']),
            new InclusionIn(
                [
                    'message' => 'Country is required',
                    'domain' => array_column($country_data->toArray(), 'id')
                ]
            )
        ]);

        $payment_method->addValidators([
            new PresenceOf(['message' => 'Payment method is required']),
            new InclusionIn(
                [
                    'message' => 'Payment Method is required',
                    'domain' => array_column($payment_method_data->toArray(), 'id')
                ]
            )
        ]);

        $shipping_method->addValidators([
            new PresenceOf(['message' => 'Shipping method is required']),
            new InclusionIn(
                [
                    'message' => 'Payment Method is required',
                    'domain' => array_column($shipping_method_data->toArray(), 'id')
                ]
            )
        ]);

        $this->add($address);
        $this->add($post_code);
        $this->add($country);
        $this->add($payment_method);
        $this->add($shipping_method);
    }
}
