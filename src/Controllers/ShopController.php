<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

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
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Controller;
use Phalcon\Tag;

/**
 * @RoutePrefix('/shop')
 */
class ShopController extends AbstractController
{
    /**
     * @Get('/cart')
     */
    public function cartAction(): void
    {
        $title = $this->translation->setTypePage()->_('title-shop-cart');

        Tag::setTitle($title);

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

        Tag::setTitle($title);

        $this->view->setVar('addRoute', '/cart/add/');
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
            !$this->security->checkToken('csrf', (string) $this->request->getPost('csrf'))
            || !$this->cart->addProduct($productID) 
        ) {
            return $this->response->setJsonContent([
                'success' => false,
                'message' => $translationMessage->_('unable-to-add-product'),
            ]);
        }

        return $this->response->setJsonContent([
            'success'  => true,
            'message'  => $translationMessage->_('product-added-successfully'),
            'newToken' => $this->security->getToken(),
        ]);
    }

    /**
     * @Get('/remove/{id:[0-9]+}')
     *
     * @param int $productID
     * 
     * @return ResponseInterface
     */
    public function removeAction(int $productID): ResponseInterface
    {
        $this->view->disable();

        $translationMessage = $this->translation->setTypeMessage();

        if (!$this->security->checkToken('csrf', (string) $this->request->getPost('csrf'))) {
            return $this->response->setJsonContent([
                'success' => false,
                'message' => $translationMessage->_('unable-to-remove-product'),
            ]);
        }

        $this->cart->removeProduct($productID);

        return $this->response->setJsonContent([
            'success'  => true,
            'message'  => $translationMessage->_('product-successfully-removed'),
            'newToken' => $this->security->getToken(),
        ]);
    }

    /**
     * @Get('/checkout')
     */
    public function checkoutAction()
    {
        $title = $this->translation->setTypePage()->_('title-shop-checkout');

        Tag::setTitle($title);

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

        // @Todo: Implement address fullfill
        //$address = UserAddress::getUserAddress((int) $user->id, [AddressType::BILLING, AddressType::SHIPPING]);

        $this->view->setVar('addressType', [AddressType::BILLING, AddressType::SHIPPING]);
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

        $paymentMethodID = (int) $post['payment_method'];

        $shippingMethodID = (int) $post['shipping_method'];

        $addresses  = [];
        $billingID  = AddressType::BILLING;
        $shippingID = AddressType::SHIPPING;
        foreach ([$billingID, $shippingID] as $type) {
            $addresses[$type] = [
                'address'   => $post["address_$type"],
                'postCode'  => $post["post_code_$type"],
                'locale'    => 'Unknown' /*$post['local']*/,
                'country'   => (int) $post["country_$type"]
            ];
        }

        $order = $this->createOrder(
            $addresses[$billingID], $addresses[$shippingID],
            $paymentMethodID, $shippingMethodID
        );

        $payment = null;
        if ($order !== null) {
            $paymentTypeID = PaymentType::PAYMENT;
            if ($this->cart->hasSubscriptionProducts()) {
                $paymentTypeID = PaymentType::RENEWAL;
            }

            $payment = Payment::createPayment($order->getOrderTotal(), $paymentTypeID, $paymentMethodID, (int) $order->id);
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

        Tag::setTitle($title);

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
     * 
     * @return mixed Order or null
     */
    private function createOrder(
        array $billing, array $shipment, int $paymentMethodID,
        int $shippingMethodID
    ): ?Order
    {
        try {
            $billingID = Address::createAddress(
                $billing['address'],
                $billing['postCode'],
                $billing['locale'],
                $billing['country']
            );

            $shipmentID = Address::createAddress(
                $shipment['address'],
                $shipment['postCode'],
                $shipment['locale'],
                $shipment['country']
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

            if (!$order->createItems(array_column($products, 'quantity', 'id'))) {
                $order->cancelOrder();

                return null;
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
