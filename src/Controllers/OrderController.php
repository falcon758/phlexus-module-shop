<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phlexus\Modules\Shop\Models\Order;
use Phlexus\Libraries\Arrays;
use Phlexus\Helpers as PhlexusHelpers;
use Phalcon\Tag;

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

        Tag::setTitle($title);

        $mainView = $this->view->getMainView();

        $this->view->setMainView(preg_replace('/\/public$/', '/default', $mainView));

        $orders = Order::getHistory((int) $this->request->get('p', null, 1));

        $groupedKey = Arrays::groupArrayByKey($orders->getItems()->toArray(), 'orderID');

        $groupedItems = Arrays::groupArray($groupedKey, ['productID', 'quantity', 'price'], 'items');

        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('orderRoute', '/order/');
        $this->view->setVar('orders', $orders);
        $this->view->setVar('groupedOrders', $groupedItems);
    }

    /**
     * @Get('/order/view')
     */
    public function viewAction(string $orderHash)
    {
        $title = $this->translation->setTypePage()->_('title-shop-order-view');

        Tag::setTitle($title);

        $mainView = $this->view->getMainView();

        $this->view->setMainView(preg_replace('/\/public$/', '/default', $mainView));

        $order = Order::getOrderByHash($orderHash);

        if (count($order) === 0) {
            return $this->response->redirect('/orders');
        }

        $groupedItems = Arrays::groupArray($order->toArray(), ['productID', 'quantity', 'price'], 'items');

        $company = PhlexusHelpers::phlexusConfig('company')->toArray();

        $this->view->setVar('company', $company);
        $this->view->setVar('order', $order);
        $this->view->setVar('groupedOrder', $groupedItems);
    }
}
