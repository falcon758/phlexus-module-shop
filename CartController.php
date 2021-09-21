<?php

declare(strict_types=1);

namespace StockOff\Modules\Shop\Controllers;

use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Controller;
use StockOff\Models\Product;
use Stripe\Checkout\Session;

/**
 * @RoutePrefix('/cart')
 *
 * @property Session $stripeCheckout
 */
class CartController extends Controller
{
    /**
     * @Get('/')
     */
    public function indexAction()
    {
        $products = [];
        if ($this->session->has('cart')) {
            $products = $this->session->get('cart');
        }

        $total = 0;
        foreach ($products as $product) {
            $total += $product['price'] * $product['quantity'];
        }

        $this->view->setVar('products', $products);
        $this->view->setVar('total', $total);
    }

    /**
     * @Post('/add/{id:[0-9]+}')
     *
     * @param int $productId
     * @return ResponseInterface
     */
    public function addAction(int $productId): ResponseInterface
    {
        if (!$this->addToCart($productId)) {
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
        $product = Product::findFirst($productId);
        if ($product === null) {
            //$this->flashSession->error('Product not found');
            return $this->response->redirect('cart');
        }

        $cart = [];
        if ($this->session->has('cart')) {
            $cart = $this->session->get('cart');
        }

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
            'success_url' => getenv('STRIPE_SUCCESS_URL'),
            'cancel_url' => getenv('STRIPE_CANCEL_URL'),
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
    }

    /**
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    private function addToCart(int $productId, int $quantity = 1): bool
    {
        $product = Product::findFirst($productId);
        if ($product === null) {
            $this->flashSession->error('Product not found');
            return false;
        }

        $product = $product->toArray();

        $cart = [];
        if ($this->session->has('cart')) {
            $cart = $this->session->get('cart');
        }

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
}
