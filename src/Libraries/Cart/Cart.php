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

use Phalcon\Di;
use Phlexus\Modules\Shop\Models\Product;

class Cart implements CartInterface
{
    /**
     * Session name
     *
     * @var string
     */
    protected CONST SESSIONNAME = 'cart';

    /**
     * Session
     *
     * @var \Phalcon\Session\Manager
     */
    protected $session;

    /**
     * Initialize cart
     */
    public function __construct() {
        $this->session = Di::getDefault()->getShared('session');
    }

    /**
     * Add product to cart
     * 
     * @param int $productId
     * @param int $quantity
     *
     * @return bool
     */
    public function addProduct(int $productId, int $quantity = 1): bool
    {
        $modelProduct = Product::findFirstByid($productId);
        if ($modelProduct === null) {
            return false;
        }

        $product = $modelProduct->toArray();

        $cart = $this->getProducts();

        $added = false;
        foreach ($cart as &$cartProduct) {
            if ($cartProduct['id'] == $productId) {
                $cartProduct['quantity'] += $quantity;
                $added = true;
                break;
            }
        }

        if ($added === false) {
            $product['quantity'] = $quantity;
            $cart[] = $product;
        }

        $this->session->set(self::SESSIONNAME, $cart);

        return true;
    }

    /**
     * Remove product from cart
     *
     * @param int $productId
     * 
     * @return bool
     */
    public function removeProduct(int $productId): bool
    {
        $cart = $this->getProducts();

        foreach ($cart as $key => $product) {
            if ($product['id'] == $productId) {
                unset($cart[$key]);
                break;
            }
        }

        $this->session->set(self::SESSIONNAME, $cart);

        return true;
    }

    /**
     * Has products
     * 
     * @return bool
     */
    public function hasProducts(): bool {
        return count($this->getProducts()) > 0;
    }

    /**
     * Get products
     * 
     * @return array
     */
    public function getProducts(): array {
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
    public function getTotalPrice(): float {
        $products = $this->getProducts();

        $total = 0;
        foreach ($products as $product) {
            $total += $product['price'] * $product['quantity'];
        }

        return $total;
    }
    
    /**
     * Remove all product from cart
     * 
     * @return bool
     */
    public function clear(): bool {
        $this->session->remove('cart');

        return true;
    }
}
