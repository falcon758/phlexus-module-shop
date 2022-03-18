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
        $translationForm = $this->translation->setTypeForm();   

        // Fields
        $address = new Text('address', [
            'required'    => true,
            'class'       => 'form-control',
            'placeholder' => $translationForm->_('field-address')
        ]);

        $postCode = new Text('post_code', [
            'required'    => true,
            'class'       => 'form-control',
            'placeholder' => $translationForm->_('field-post-code')
        ]);

        $countryData = Country::find();
        $country = new Select(
            'country',
            $countryData,
            [
                'using'       => ['id', 'country'],
                'required'    => true,
                'class'       => 'form-control',
                'placeholder' => $translationForm->_('field-country')
            ]
        );

        $paymentMethodData = PaymentMethod::find();
        $paymentMethod = new Select(
            'payment_method',
            $paymentMethodData,
            [
                'using'       => ['id', 'name'],
                'required'    => true,
                'class'       => 'form-control',
                'placeholder' => $translationForm->_('field-payment-method')
            ]
        );

        $shippingMethodData = ShippingMethod::find();
        $shippingMethod = new Select(
            'shipping_method',
            $shippingMethodData,
            [
                'using'       => ['id', 'name'],
                'required'    => true,
                'class'       => 'form-control',
                'placeholder' => $translationForm->_('field-shipping-method')
            ]
        );

        $translationMessage = $this->translation->setTypeMessage();

        // Validators
        $address->addValidators([
            new PresenceOf(['message' => $translationMessage->_('field-address-required')]),
            new Regex(
                [
                    'pattern' => '/^[a-zA-Z0-9\s.-]*$/',
                    'message' => $translationMessage->_('field-address-invalid-characters'),
                ]
            )
        ]);

        $postCode->addValidators([
            new PresenceOf(['message' => $translationMessage->_('field-post-code-required')]),
            new Regex(
                [
                    'pattern' => '/^[0-9]+-[0-9]+$/',
                    'message' => $translationMessage->_('field-post-code-invalid-characters'),
                ]
            )
        ]);

        $countryRequired = $translationMessage->_('field-country-required');
        $country->addValidators([
            new PresenceOf(['message' => $countryRequired]),
            new InclusionIn(
                [
                    'message' => $countryRequired,
                    'domain'  => array_column($countryData->toArray(), 'id')
                ]
            )
        ]);

        $paymentMethodRequired = $translationMessage->_('field-payment-method-required');
        $paymentMethod->addValidators([
            new PresenceOf(['message' => $paymentMethodRequired]),
            new InclusionIn(
                [
                    'message' => $paymentMethodRequired,
                    'domain'  => array_column($paymentMethodData->toArray(), 'id')
                ]
            )
        ]);

        $shippingMethodRequired = $translationMessage->_('field-shipping-method-required');
        $shippingMethod->addValidators([
            new PresenceOf(['message' => $shippingMethodRequired]),
            new InclusionIn(
                [
                    'message' => $shippingMethodRequired,
                    'domain' => array_column($shippingMethodData->toArray(), 'id')
                ]
            )
        ]);

        $this->add($address);
        $this->add($postCode);
        $this->add($country);
        $this->add($paymentMethod);
        $this->add($shippingMethod);
    }
}
