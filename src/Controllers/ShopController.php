<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Controller;
use Phlexus\Modules\Shop\Models\Product;
use Phlexus\Modules\Shop\Models\Address;
use Phlexus\Modules\Shop\Models\AddressType;
use Phlexus\Modules\Shop\Models\UserAddress;
use Phlexus\Modules\Shop\Models\Order;
use Phlexus\Modules\Shop\Form\CheckoutForm;
use Phlexus\Modules\BaseUser\Models\User;
use Stripe\Checkout\Session;


/**
 * @RoutePrefix('/shop')
 *
 */
class ShopController extends Controller
{
    /**
     * @Get('/')
     */
    public function cartAction(): void
    {
        $products = $this->getProductsOnCart();
        
        $this->view->setVar('checkoutRoute', '/checkout');
        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('products', $products);
        $this->view->setVar('total', $this->getProductsTotalPrice());
    }

    public function productsAction(): void
    {
        $this->view->setVar('saveRoute', '/cart/add/');
        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('products', Product::find()->toArray());
    }

    /**
     * @Post('/add/{id:[0-9]+}')
     *
     * @param int $productId
     * @return ResponseInterface
     */
    public function addAction(int $productId): ResponseInterface
    {
        if (!$this->addToCart($productId) 
            || !$this->security->checkToken('csrf', $this->request->getPost('csrf', null))) {
            return $this->response->setJsonContent([
                'success' => false,
            ]);
        }

        return $this->response->setJsonContent([
            'success' => true,
        ]);
    }

    /**
     * @Get('/buy/{id:[0-9]+}')
     *
     * @param int $productId
     * @return ResponseInterface
     */
    public function buyAction(int $productId): ResponseInterface
    {
        if (!$this->addToCart($productId)) {
            return $this->response->redirect('products');
        }

        return $this->response->redirect('cart');
    }

    /**
     * @Get('/remove/{id:[0-9]+}')
     *
     * @param int $productId
     * @return ResponseInterface
     */
    public function removeAction(int $productId): ResponseInterface
    {
        if (!$this->security->checkToken('csrf', $this->request->getPost('csrf', null))) {
            return $this->response->setJsonContent([
                'success' => false,
            ]);
        }

        $product = Product::findFirst($productId);
        if ($product === null) {
            //$this->flashSession->error('Product not found');
            return $this->response->redirect('cart');
        }

        $cart = $this->getProductsOnCart();

        foreach ($cart as $key => $product) {
            if ($product['id'] == $productId) {
                unset($cart[$key]);
                break;
            }
        }

        $this->session->set('cart', $cart);

        return $this->response->redirect('cart');
    }

    /**
     * @Get('/checkout')
     */
    public function checkoutAction()
    {
        $products = $this->getProductsOnCart();

        if (count($products) === 0) {
            return $this->response->redirect('cart');
        }

        $this->view->setVar('products', $products);
        $this->view->setVar('orderRoute', '/checkout/order');
        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('total', $this->getProductsTotalPrice());
        $this->view->setVar('checkoutForm', new CheckoutForm());
    }

    /**
     * @Get('/checkout/order')
     */
    public function orderAction()
    {
        $products = $this->getProductsOnCart();

        if (count($products) === 0) {
            return $this->response->redirect('cart');
        }

        $form = new CheckoutForm(false);

        $post = $this->request->getPost();

        if (!$form->isValid($post)) {
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
            'post_code' => $postCode,
            'locale'    => $locale,
            'country'   => $country
        ];

        $shipment = [
            'address'   => $address,
            'post_code' => $postCode,
            'locale'    => $locale,
            'country'   => $country
        ];

        if ($this->createOrder($billing, $shipment, $paymentMethod, $shippingMethod, $country)) {
            return $this->response->redirect('checkout/success');
        } else {
            return $this->response->redirect('checkout/cancel');
        }
    }

    /**
     * @Post('/checkout/stripe')
     */
    public function stripeAction(): ResponseInterface
    {
        $checkoutSession = $this->stripeCheckout::create([
            'payment_method_types' => ['card', 'sepa_debit'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => 2000,
                        'product_data' => [
                            'name' => 'Stubborn Attachments',
                            'images' => ["https://i.imgur.com/EHyR2nP.png"],
                        ],
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => getenv('CHECKOUT_SUCCESS_URL'),
            'cancel_url' => getenv('CHECKOUT_CANCEL_URL'),
        ]);

        return $this->response->setJsonContent([
            'id' => $checkoutSession->id,
        ]);
    }

    /**
     * @Get('/checkout/success')
     */
    public function successAction()
    {
        $this->session->remove('cart');
    }

    /**
     * @Get('/checkout/cancel')
     */
    public function cancelAction()
    {
        return $this->response->redirect('cart');
    }

    /**
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    private function addToCart(int $productId, int $quantity = 1): bool
    {
        $product = Product::findFirstByid($productId);
        if ($product === null) {
            $this->flashSession->error('Product not found');
            return false;
        }

        $product = $product->toArray();

        $cart = $this->getProductsOnCart();

        $added = false;
        foreach ($cart as &$product) {
            if ($product['id'] == $productId) {
                $product['quantity'] += $quantity;
                $added = true;
                break;
            }
        }

        if ($added === false) {
            $product['quantity'] = $quantity;
            $cart[] = $product;
        }

        $this->session->set('cart', $cart);

        return true;
    }

    /**
     * @return bool
     */
    private function hasProductsOnCart(): bool {
        return count($this->getProductsOnCart) > 0;
    }

    /**
     * @return array
     */
    private function getProductsOnCart(): array {
        $products = [];

        if ($this->session->has('cart')) {
            $products = $this->session->get('cart');
        }

        return $products;
    }

    /**
     * @return float
     */
    private function getProductsTotalPrice(): float {
        $products = $this->getProductsOnCart();

        $total = 0;
        foreach ($products as $product) {
            $total += $product['price'] * $product['quantity'];
        }

        return $total;
    }

    /**
     * Create order
     * 
     * @param array $billing        Billing address
     * @param array $shipment       Shipment address
     * @param array $paymentMethod  Payment method
     * @param array $shippingMethod Shipping address
     * 
     * @return bool
     */
    private function createOrder(
        array $billing, array $shipment, int $paymentMethod,
        int $shippingMethod, int $country
    ): bool {
        $billingId = Address::createAddress(
            $billing['address'],
            $billing['post_code'],
            $billing['locale'],
            $country
        );

        $shipmentId = Address::createAddress(
            $shipment['address'],
            $shipment['post_code'],
            $shipment['locale'],
            $country
        );

        // Get current logged user
        $user = User::getUser();

        $userId = null;

        if ($user) {
            $userId = (int) $user->id;
        } else {
            // @ToDo: Change to a temp user or force register/login
            $userId = 1;
        }

        $billingUserAddress = UserAddress::createUserAddress(
            $userId,
            (int) $billingId->id,
            AddressType::BILLING
        );

        $shippingUserAddress = UserAddress::createUserAddress(
            $userId,
            (int) $shipmentId->id,
            AddressType::SHIPPING
        );

        return Order::createOrder(
            $userId, (int) $billingUserAddress->id, (int) $shippingUserAddress->id,
            $paymentMethod, $shippingMethod
        ) ? true : false;
    }
}
