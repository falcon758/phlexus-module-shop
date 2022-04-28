<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phlexus\Modules\Shop\Models\Payment;

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

        $payments = Payment::getInPayment();

        $this->view->setVar('csrfToken', $this->security->getToken());
        $this->view->setVar('payRoute', '/payment/pay/');
        $this->view->setVar('payments', $payments);
    }
}
