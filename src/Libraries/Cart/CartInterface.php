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

interface CartInterface
{
    /**
     * Add product to cart
     * 
     * @param int $productID
     * @param int $quantity
     *
     * @return bool
     */
    public function addProduct(int $productID, int $quantity = 1): bool;

    /**
     * Remove product from cart
     *
     * @param int $productID
     * 
     * @return bool
     */
    public function removeProduct(int $productID): bool;

    /**
     * Has products
     * 
     * @return bool
     */
    public function hasProducts(): bool;

    /**
     * Get products
     * 
     * @return array
     */
    public function getProducts(): array;

    /**
     * Get cart total price
     * 
     * @return float
     */
    public function getTotalPrice(): float;

    /**
     * Remove all product from cart
     * 
     * @return bool
     */
    public function clear(): bool;
}
