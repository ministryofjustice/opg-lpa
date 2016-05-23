<?php
namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;

use Opg\Lpa\DataModel\Lpa\Payment\Payment;


class GovPayPaymentController extends AbstractLpaController {

    public function indexAction()
    {
        $lpa = $this->getLpa();

        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        // session container for storing online payment email address
        $container = new Container('paymentEmail');

        // make payment by cheque
        if($this->params()->fromQuery('pay-by-cheque')) {

            $lpa->payment->method = Payment::PAYMENT_TYPE_CHEQUE;

            if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in FeeReductionController');
            }

            // send email
            $this->getServiceLocator()->get('Communication')->sendRegistrationCompleteEmail(
                $lpa,
                $this->url()->fromRoute('lpa/view-docs', ['lpa-id' => $lpa->id], ['force_canonical' => true])
            );

            // to complete page
            return $this->redirect()->toRoute($this->getFlowChecker()->nextRoute($currentRouteName), ['lpa-id' => $lpa->id]);

        } elseif($this->params()->fromQuery('retry') && ($lpa->payment->method == Payment::PAYMENT_TYPE_CARD) && ($container->email != null)) {

            return $this->payOnline($lpa);
        }

        //-------------------------------------------

        // Payment form

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\PaymentForm');

        if($this->request->isPost()) {
            $postData = $this->request->getPost();

            // set data for validation
            $form->setData($postData);

            if($form->isValid()) {

                $lpa->payment->method = Payment::PAYMENT_TYPE_CARD;

                // persist data
                if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                    throw new \RuntimeException('API client failed to set repeat case number for id: '.$lpa->id);
                }

                // set paymentEmail in session container.
                $container->email = $form->getData()['email'];

                return $this->payOnline($lpa);

            } // if($form->isValid())
        }
        else {
            // when landing on payment page, show the payment form

            $data = [];
            if($this->getLpa()->payment instanceof Payment) {
                $data['method'] =  $this->getLpa()->payment->method;
            }

            $container = new Container('paymentEmail');
            if(isset($container->email)) {
                $data['email'] = $container->email;
            }

            $form->bind($data);
        }

        return new ViewModel([
            'form'=>$form,
            'payByChequeRoute' => $this->url()->fromRoute('lpa/payment', ['lpa-id'=>$this->getLpa()->id], ['query'=>['pay-by-cheque'=>true]]),
        ]);

    }

}
