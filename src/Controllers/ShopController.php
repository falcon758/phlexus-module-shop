<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Controller;
use Phlexus\Modules\Shop\Libraries\Cart\Cart;
use Phlexus\Modules\Shop\Models\Product;
use Phlexus\Modules\Shop\Models\Address;
use Phlexus\Modules\Shop\Models\AddressType;
use Phlexus\Modules\Shop\Models\UserAddress;
use Phlexus\Modules\Shop\Models\Order;
use Phlexus\Modules\Shop\Models\Item;
use Phlexus\Modules\Shop\Models\Payment;
use Phlexus\Modules\Shop\Models\PaymentMethod;
use Phlexus\Modules\Shop\Models\PaymentType;
use Phlexus\Modules\Shop\Form\CheckoutForm;
use Phlexus\Modules\BaseUser\Models\User;
use Phlexus\Modules\Shop\Libraries\Payments\PaymentFactory;


/**
 * @RoutePrefix('/shop')
 *
 */
class ShopController extends AbstractController
{
    /**
     * @Get('/cart')
     */
    public function cartAction(): void
    {
        $title = $this->translation->setTypePage()->_('title-shop-cart');

        $this->tag->setTitle($title);

        $products = $this->cart->getProducts();
        
        $this->view->setVar('checkoutRoute', '/checkout');
        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('products', $products);
        $this->view->setVar('total', $this->cart->getTotalPrice());
    }

    /**
     * @Get('/products')
     */
    public function productsAction(): void
    {
        $title = $this->translation->setTypePage()->_('title-shop-products');

        $this->tag->setTitle($title);

        $this->view->setVar('saveRoute', '/cart/add/');
        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('products', Product::find());
    }

    /**
     * @Post('/add/{id:[0-9]+}')
     *
     * @param int $productID
     * @return ResponseInterface
     */
    public function addAction(int $productID): ResponseInterface
    {
        $this->view->disable();

        $translationMessage = $this->translation->setTypeMessage();

        if (
            !$this->security->checkToken('csrf', $this->request->getPost('csrf', null))
            || !$this->cart->addProduct($productID) 
        ) {
            return $this->response->setJsonContent([
                'success' => false,
                'message' => $translationMessage->_('unable-to-add-product'),
            ]);
        }

        return $this->response->setJsonContent([
            'success' => true,
            'message' => $translationMessage->_('product-added-successfully'),
        ]);
    }

    /**
     * @Get('/remove/{id:[0-9]+}')
     *
     * @param int $productID
     * @return ResponseInterface
     */
    public function removeAction(int $productID): ResponseInterface
    {
        $this->view->disable();

        $translationMessage = $this->translation->setTypeMessage();

        if (!$this->security->checkToken('csrf', $this->request->getPost('csrf', null))) {
            return $this->response->setJsonContent([
                'success' => false,
                'message' => $translationMessage->_('unable-to-remove-product'),
            ]);
        }

        $this->cart->removeProduct($productID);

        return $this->response->setJsonContent([
            'success' => true,
            'message' => $translationMessage->_('product-successfully-removed'),
        ]);
    }

    /**
     * @Get('/checkout')
     */
    public function checkoutAction()
    {
        $title = $this->translation->setTypePage()->_('title-shop-checkout');

        $this->tag->setTitle($title);

        $products = $this->cart->getProducts();

        $translationMessage = $this->translation->setTypeMessage();

        if (count($products) === 0) {
            $this->flash->error($translationMessage->_('empty-cart'));

            return $this->response->redirect('cart');
        }

        $user = User::getUser();
        
        if ($user === null) {
            $this->flash->warning($translationMessage->_('login-before-checkout'));

            return $this->response->redirect('/user');
        }

        $this->view->setVar('products', $products);
        $this->view->setVar('orderRoute', '/checkout/order');
        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('total', $this->cart->getTotalPrice());
        $this->view->setVar('checkoutForm', new CheckoutForm());
    }

    /**
     * @Get('/checkout/order')
     */
    public function orderAction()
    {
        $this->view->disable();

        if (!$this->cart->hasProducts()) {
            return $this->response->redirect('cart');
        }

        $form = new CheckoutForm(false);

        $post = $this->request->getPost();

        if (!$form->isValid($post)) {
            foreach ($form->getMessages() as $message) {
                $this->flash->error($message->getMessage());
            }

            return $this->response->redirect('checkout');
        }

        $address = $post['address'];

        $postCode = $post['post_code'];

        $countryID = (int) $post['country'];        

        $locale = 'Unknown' /*$post['local']*/;

        $paymentMethodID = (int) $post['payment_method'];

        $shippingMethodID = (int )$post['shipping_method'];

        $billing = [
            'address'   => $address,
            'postCode'  => $postCode,
            'locale'    => $locale,
            'country'   => $countryID
        ];

        $shipment = [
            'address'   => $address,
            'postCode'  => $postCode,
            'locale'    => $locale,
            'country'   => $countryID
        ];

        $order = $this->createOrder(
            $billing, $shipment, $paymentMethodID,
            $shippingMethodID, $countryID
        );

        $payment = null;
        if ($order !== null) {
            $paymentTypeID = PaymentType::PAYMENT;
            if ($this->cart->hasSubscriptionProducts()) {
                $paymentTypeID = PaymentType::RENEWAL;
            }

            $payment = Payment::createPayment($paymentTypeID, $paymentMethodID, (int) $order->id);
        }

        if ($payment !== null) {
            $paymentProcess = (new PaymentFactory())->build($payment);
            return $paymentProcess->startPayment();
        } else {
            return $this->response->redirect('order/cancel');
        }
    }

    /**
     * @Get('/order/success')
     */
    public function successAction()
    {
        $title = $this->translation->setTypePage()->_('title-order-success');

        $this->tag->setTitle($title);

        $this->cart->clear();
    }

    /**
     * @Get('/order/cancel')
     */
    public function cancelAction()
    {
        $this->view->disable();

        return $this->response->redirect('cart');
    }

    /**
     * Create order
     * 
     * @param array $billing          Billing address
     * @param array $shipment         Shipment address
     * @param array $paymentMethodID  Payment method
     * @param array $shippingMethodID Shipping address
     * @param array $countryID        Address country
     * 
     * @return mixed Order or null
     */
    private function createOrder(
        array $billing, array $shipment, int $paymentMethodID,
        int $shippingMethodID, int $countryID
    ) {
        try {
            $billingID = Address::createAddress(
                $billing['address'],
                $billing['postCode'],
                $billing['locale'],
                $countryID
            );

            $shipmentID = Address::createAddress(
                $shipment['address'],
                $shipment['postCode'],
                $shipment['locale'],
                $countryID
            );

            // Get current logged user
            $user = User::getUser();

            $userID = null;

            if ($user !== null) {
                $userID = (int) $user->id;
            } else {
                return null;
            }

            $billingUserAddress = UserAddress::createUserAddress(
                $userID,
                (int) $billingID->id,
                AddressType::BILLING
            );

            $shippingUserAddress = UserAddress::createUserAddress(
                $userID,
                (int) $shipmentID->id,
                AddressType::SHIPPING
            );

            $order = Order::createOrder(
                $userID, (int) $billingUserAddress->id, (int) $shippingUserAddress->id,
                $paymentMethodID, $shippingMethodID
            );

            $products = $this->cart->getProducts();

            foreach ($products as $product) {
                if (!Item::createItem((int) $product['id'], (int) $order->id)) {
                    $order->cancelOrder();

                    return null;
                }
            }
        } catch(\Exception $e) {
            $errorMessage = $this->translation->setTypeMessage()
                                              ->_('unable-to-create-order');

            $this->flash->error($errorMessage);

            return null;
        }

        return $order;
    }
}
