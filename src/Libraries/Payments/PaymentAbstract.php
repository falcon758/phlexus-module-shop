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

namespace Phlexus\Modules\Shop\Libraries\Payments;

use Phlexus\Modules\Shop\Models\Order;
use Phalcon\Di;
use Phalcon\Url;
use Phalcon\Http\Response;
use Phalcon\Flash\Session as FlashSession;

abstract class PaymentAbstract implements PaymentInterface
{
    /**
     * Url
     * 
     * @var URL
     */
    protected URL $url;

    /**
     * Response
     * 
     * @var Response
     */
    protected Response $response;

    /**
     * Flash
     * 
     * @var FlashSession
     */
    protected FlashSession $flash;

    /**
     * Order
     * 
     * @var Order
     */
    protected Order $order;

    /**
     * Construct Payment
     * 
     * @param string $order Order to process
     */
    public function __construct(Order $order) {
        $di = Di::getDefault();

        $url          = $di->getShared('url');
        $httpResponse = $di->getShared('response');
        $flash        = $di->getShared('flash');

        $this->url      = $url;
        $this->response = $httpResponse;
        $this->flash    = $flash;
        $this->order    = $order;
    }
}