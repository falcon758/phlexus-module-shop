<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phlexus\Modules\Shop\Models\Order;

/**
 * @RoutePrefix('/order')
 */
class OrderController extends AbstractController
{
    /**
     * @Get('/order/index')
     */
    public function indexAction()
    {
        $title = $this->translation->setTypePage()->_('title-shop-orders');

        $this->tag->setTitle($title);

        $mainView = $this->view->getMainView();

        $this->view->setMainView(preg_replace('/\/public$/', '/default', $mainView));

        $orders = Order::getHistory();

        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('orderRoute', '/payment/pay/');
        $this->view->setVar('orders', $orders);
    }
}
