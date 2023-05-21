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

namespace Phlexus\Modules\Shop\Libraries\Cart;

use Phalcon\Di\Di;
use Phlexus\Modules\Shop\Models\Product;
use Phalcon\Session\Manager;

class Cart implements CartInterface
{
    /**
     * Session name
     *
     * @var string
     */
    protected CONST SESSIONNAME = 'cart';

    /**
     * Products Limit
     *
     * @var int
     */
    CONST PRODUCT_LIMIT = 1000;

    /**
     * Session
     *
     * @var \Phalcon\Session\Manager
     */
    protected Manager $session;

    /**
     * Initialize cart
     */
    public function __construct()
    {
        $this->session = Di::getDefault()->getShared('session');
    }

    /**
     * Add product to cart
     * 
     * @param int $productID
     * @param int $quantity
     *
     * @return bool
     */
    public function addProduct(int $productID, int $quantity = 1): bool
    {
        $modelProduct = Product::findFirstByid($productID);
        if ($modelProduct === null) {
            return false;
        }

        $product = $modelProduct->toArray();

        $cart = $this->getProducts();

        $hasProduct = false;
        foreach ($cart as &$cartProduct) {
            if ($cartProduct['id'] == $productID) {
                if (!$this->canAddProduct($cartProduct)) {
                    return false;
                }

                $cartProduct['quantity'] += $quantity;
                $hasProduct = true;
                break;
            }
        }

        if ($hasProduct === false) {
            if (!$this->canAddProduct($product)) {
                return false;
            }

            $product['quantity'] = $quantity;
            $cart[] = $product;
        }

        $this->session->set(self::SESSIONNAME, $cart);

        return true;
    }

    /**
     * Remove product from cart
     *
     * @param int $productID
     * 
     * @return bool
     */
    public function removeProduct(int $productID): bool
    {
        $cart = $this->getProducts();

        foreach ($cart as $key => $product) {
            if ($product['id'] == $productID) {
                unset($cart[$key]);
                break;
            }
        }

        $this->session->set(self::SESSIONNAME, $cart);

        return true;
    }

    /**
     * Has subscription products
     * 
     * @return bool
     */
    public function hasSubscriptionProducts(): bool
    {
        $cart = $this->getProducts();

        foreach ($cart as $product) {
            if (((int) $product['isSubscription']) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Has products
     * 
     * @return bool
     */
    public function hasProducts(): bool
    {
        return count($this->getProducts()) > 0;
    }

    /**
     * Get products
     * 
     * @return array
     */
    public function getProducts(): array
    {
        $products = [];

        if ($this->session->has(self::SESSIONNAME)) {
            $products = $this->session->get(self::SESSIONNAME);
        }

        return $products;
    }

    /**
     * Get cart total price
     * 
     * @return float
     */
    public function getTotalPrice(): float
    {
        $products = $this->getProducts();

        $total = 0;
        foreach ($products as $product) {
            $total += $product['quantity'] * $product['price'];
        }

        return $total;
    }
    
    /**
     * Remove all product from cart
     * 
     * @return bool
     */
    public function clear(): bool
    {
        $this->session->remove('cart');

        return true;
    }

    /**
     * Can add product
     * 
     * @param $product Product cart data
     * 
     * @return bool
     */
    private function canAddProduct(array $product): bool
    {
        $isSubscription = ((int) $product['isSubscription']) === 1;
        $quantity = (int) $product['quantity'] ?? 0;

        if (
            ($isSubscription && $quantity === 1)
            || $quantity > self::PRODUCT_LIMIT
            || $this->hasSubscriptionProducts()
        ) {
            return false;
        }

        return true;
    }
}
