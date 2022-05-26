<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phlexus\Modules\Shop\Models\Payment;
use Phlexus\Modules\Shop\Models\PaymentType;
use Phlexus\Modules\Shop\Libraries\Payments\PaymentFactory;

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

        $payments = Payment::getInPayment();

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

        $payments = Payment::getHistory();

        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('payments', $payments);
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
