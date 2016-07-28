<?php

namespace Application\Controller\Authenticated\Lpa;

use Opg\Lpa\DataModel\Lpa\Payment\Payment;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;

class CheckoutController extends AbstractLpaController {

    public function indexAction(){

        return new ViewModel();

    }

    public function chequeAction(){

        $lpa = $this->getLpa();

        $lpa->payment->method = Payment::PAYMENT_TYPE_CHEQUE;

        if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
            throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in CheckoutController');
        }

        //---

        return $this->getNextSectionRedirect();

    }

    public function payAction(){

        die('pay');

    }

    public function worldpayAction(){

        die('worldpay');

    }

}
