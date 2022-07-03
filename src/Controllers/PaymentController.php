<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phlexus\Modules\Shop\Models\Payment;
use Phlexus\Modules\Shop\Models\PaymentType;
use Phlexus\Modules\Shop\Libraries\Payments\PaymentFactory;
use Phlexus\Libraries\Arrays;

/**
 * @RoutePrefix('/payment')
 */
class PaymentController extends AbstractController
{
    /**
     * @Get('/payment/index')
     */
    public function indexAction()
    {
        $title = $this->translation->setTypePage()->_('title-shop-payments');

        $this->tag->setTitle($title);

        $mainView = $this->view->getMainView();

        $this->view->setMainView(preg_replace('/\/public$/', '/default', $mainView));

        $payments = Payment::getInPayment((int) $this->request->get('p', null, 1));

        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('payRoute', '/payment/pay/');
        $this->view->setVar('payments', $payments);
    }
 
    /**
     * @Get('/payment/history')
     */
    public function historyAction()
    {
        $title = $this->translation->setTypePage()->_('title-shop-payments-history');

        $this->tag->setTitle($title);

        $mainView = $this->view->getMainView();

        $this->view->setMainView(preg_replace('/\/public$/', '/default', $mainView));

        $payments = Payment::getHistory((int) $this->request->get('p', null, 1));

        $groupedKey = Arrays::groupArrayByKey($payments->getItems()->toArray(), 'orderID');

        $groupedItems = Arrays::groupArray($groupedKey, ['productID', 'quantity', 'price'], 'items');

        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('viewRoute', '/payment/');
        $this->view->setVar('payments', $payments);
        $this->view->setVar('groupedPayments', $groupedItems);
    }

    /**
     * @Get('/payment/view')
     */
    public function viewAction(string $paymentHash)
    {
        $title = $this->translation->setTypePage()->_('title-shop-payment-view');

        $this->tag->setTitle($title);

        $mainView = $this->view->getMainView();

        $this->view->setMainView(preg_replace('/\/public$/', '/default', $mainView));

        $payment = Payment::getPaymentByHash($paymentHash);

        if (count($payment) === 0) {
            return $this->response->redirect('/payment/history');
        }

        $groupedItems = Arrays::groupArray($payment->toArray(), ['productID', 'quantity', 'price'], 'items');

        $this->view->setVar('payment', $order);
        $this->view->setVar('groupedOrder', $groupedItems);
    }

    /**
     * @Get('/payment/pay')
     */
    public function payAction(string $paymentHash)
    {
        $this->view->disable();
        
        $payment = Payment::getUserPayment($paymentHash);

        if (!$payment) {
            return $this->response->redirect('/payments');
        }
        
        $paymentProcess = (new PaymentFactory())->build($payment);
        return $paymentProcess->startPayment();
    }
}
