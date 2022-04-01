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
use Phlexus\Modules\Shop\Models\PaymentMethod;
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
     * Initialize
     */
    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * @Get('/cart')
     */
    public function cartAction(): void
    {
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
        $products = $this->cart->getProducts();

        $translationMessage = $this->translation->setTypeMessage();

        if (count($products) === 0) {
            $this->flash->error($translationMessage->_('empty-cart'));

            return $this->response->redirect('cart');
        }

        $user = User::getUser();
        
        if (!isset($user->id)) {
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

        $country = (int) $post['country'];        

        $locale = 'Unknown' /*$post['local']*/;

        $paymentMethod = (int) $post['payment_method'];

        $shippingMethod = (int )$post['shipping_method'];

        $billing = [
            'address'   => $address,
            'postCode'  => $postCode,
            'locale'    => $locale,
            'country'   => $country
        ];

        $shipment = [
            'address'   => $address,
            'postCode'  => $postCode,
            'locale'    => $locale,
            'country'   => $country
        ];

        $order = $this->createOrder($billing, $shipment, $paymentMethod, $shippingMethod, $country);

        if ($order !== null) {
            $payment = (new PaymentFactory())->build($order);
            return $payment->startPayment();
        } else {
            return $this->response->redirect('order/cancel');
        }
    }

    /**
     * @Get('/order/success')
     */
    public function successAction()
    {
        $this->cart->clear();
    }

    /**
     * @Get('/order/cancel')
     */
    public function cancelAction()
    {
        return $this->response->redirect('cart');
    }

    /**
     * Create order
     * 
     * @param array $billing        Billing address
     * @param array $shipment       Shipment address
     * @param array $paymentMethod  Payment method
     * @param array $shippingMethod Shipping address
     * 
     * @return mixed Order or null
     */
    private function createOrder(
        array $billing, array $shipment, int $paymentMethod,
        int $shippingMethod, int $country
    ) {
        try {
            $billingID = Address::createAddress(
                $billing['address'],
                $billing['postCode'],
                $billing['locale'],
                $country
            );

            $shipmentID = Address::createAddress(
                $shipment['address'],
                $shipment['postCode'],
                $shipment['locale'],
                $country
            );

            // Get current logged user
            $user = User::getUser();

            $userID = null;

            if (isset($user->id)) {
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
                $paymentMethod, $shippingMethod
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
