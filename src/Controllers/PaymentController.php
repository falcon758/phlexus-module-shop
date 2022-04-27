<?php

declare(strict_types=1);

namespace Phlexus\Modules\Shop\Controllers;

use Phalcon\Http\ResponseInterface;
use Phlexus\Modules\Shop\Models\Payment;

/**
 * @RoutePrefix('/payment')
 */
class PaymentController extends AbstractController
{
    /**
     * @Get('/payment/index')
     */
    public function indexAction(): ResponseInterface
    {
        $title = $this->translation->setTypePage()->_('title-shop-payments');

        $this->tag->setTitle($title);

        $payments = Payment::getInPayment();

        $this->view->setVar('payments', $payments);
    }
}
