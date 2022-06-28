<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phlexus\Modules\Shop\Models\Order;
use Phlexus\Libraries\Arrays;

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

        $orders = Order::getHistory((int) $this->request->get('p', null, 1));

        $groupedKey = Arrays::groupArrayByKey($orders->getItems()->toArray(), 'orderID');

        $groupedItems = Arrays::groupArray($groupedKey, ['productID', 'quantity', 'price'], 'items');

        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('orderRoute', '/order/');
        $this->view->setVar('orders', $orders);
        $this->view->setVar('groupedOrder', $groupedItems);
    }
}
