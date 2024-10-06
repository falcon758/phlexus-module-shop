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
use Phlexus\Modules\Shop\Models\AddressType;
use Phlexus\Modules\Shop\Models\PaymentMethod;
use Phlexus\Modules\Shop\Models\ShippingMethod;
use Phlexus\Libraries\Translations\TranslationInterface;
use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Select;
use Phalcon\Forms\Element\Check;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Filter\Validation\Validator\InclusionIn;
use Phalcon\Filter\Validation\Validator\Regex;

class checkoutForm extends CaptchaForm
{
    private TranslationInterface $translationForm;

    private TranslationInterface $translationMessage;

    /**
     * Initialize form
     */
    public function initialize()
    {
        $this->translationForm    = $this->translation->setPage()->setTypeForm();
        $this->translationMessage = $this->translation->setPage()->setTypeMessage();

        // Fields
        $this->buildPersonalInfo();

        $this->buildPaymentMethod();

        $this->buildShippingMethod();

        $sameAddress = (
            new Check('same_address', [
                'class' => 'form-control'
            ])
        )->setLabel($this->translationForm->_('field-same-address'));

        $this->add($sameAddress);

        $this->buildAddress(AddressType::BILLING);
        $this->buildAddress(AddressType::SHIPPING);
    }

    /**
     * Build personal info fields
     */
    private function buildPersonalInfo()
    {
        $translationForm    = $this->translationForm;
        $translationMessage = $this->translationMessage;

        $name = new Text('name', [
            'class'       => 'form-control',
            'placeholder' => $translationForm->_('field-name')
        ]);

        $taxNumber = new Text('tax_number', [
            'class'       => 'form-control',
            'placeholder' => $translationForm->_('field-tax-number')
        ]);

        $name->addValidators([
            new PresenceOf(['message' => $translationMessage->_('field-name-required')]),
            new Regex(
                [
                    'pattern' => '/^[a-zA-Z0-9\s.-]*$/',
                    'message' => $translationMessage->_('field-name-invalid-characters'),
                ]
            )
        ]);

        $taxNumber->addValidators([
            new PresenceOf(['message' => $translationMessage->_('field-tax-number-required')]),
            new Regex(
                [
                    'pattern' => '/^[a-zA-Z0-9]+$/',
                    'message' => $translationMessage->_('field-tax-number-invalid-characters'),
                ]
            )
        ]);

        $this->add($name);
        $this->add($taxNumber);
    }

    /**
     * Build payment method fields
     */
    private function buildPaymentMethod()
    {
        $paymentMethodData = PaymentMethod::find();
        $paymentMethod = new Select(
            'payment_method',
            $paymentMethodData,
            [
                'using'       => ['id', 'name'],
                'class'       => 'form-control',
                'placeholder' => $this->translationForm->_('field-payment-method')
            ]
        );

        $paymentMethodRequired = $this->translationMessage->_('field-payment-method-required');
        $paymentMethod->addValidators([
            new PresenceOf(['message' => $paymentMethodRequired]),
            new InclusionIn(
                [
                    'message' => $paymentMethodRequired,
                    'domain'  => array_column($paymentMethodData->toArray(), 'id')
                ]
            )
        ]);

        $this->add($paymentMethod);
    }

    /**
     * Build shipping method fields
     */
    private function buildShippingMethod()
    {
        $shippingMethodData = ShippingMethod::find();
        $shippingMethod = new Select(
            'shipping_method',
            $shippingMethodData,
            [
                'using'       => ['id', 'name'],
                'class'       => 'form-control',
                'placeholder' => $this->translationForm->_('field-shipping-method')
            ]
        );

        $shippingMethodRequired = $this->translationMessage->_('field-shipping-method-required');
        $shippingMethod->addValidators([
            new PresenceOf(['message' => $shippingMethodRequired]),
            new InclusionIn(
                [
                    'message' => $shippingMethodRequired,
                    'domain' => array_column($shippingMethodData->toArray(), 'id')
                ]
            )
        ]);

        $this->add($shippingMethod);
    }

    /**
     * Build address fields
     */
    private function buildAddress($type)
    {
        $translationForm = $this->translationForm;   

        // Fields
        $address = new Text("address_$type", [
            'class'       => 'form-control',
            'placeholder' => $translationForm->_('field-address')
        ]);

        $postCode = new Text("post_code_$type", [
            'class'       => 'form-control',
            'placeholder' => $translationForm->_('field-post-code')
        ]);

        $countryData = Country::find();
        $country = new Select(
            "country_$type",
            $countryData,
            [
                'using'       => ['id', 'country'],
                'class'       => 'form-control',
                'placeholder' => $translationForm->_('field-country')
            ]
        );

        $translationMessage =  $this->translationMessage;

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
                    'pattern' => '/^[a-zA-Z0-9\-]+$/',
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

        $this->add($address);
        $this->add($postCode);
        $this->add($country);
    }
}
