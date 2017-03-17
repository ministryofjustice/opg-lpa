<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;

class IncomeAndUniversalCreditController extends AbstractLpaController
{

    protected $contentHeader = 'registration-partial.phtml';

    public function indexAction()
    {
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\IncomeAndUniversalCreditForm');

        if($this->request->isPost()) {
            $postData = $this->request->getPost();

            // set data for validation
            $form->setData($postData);

            // only validate reducedFeeLowIncome if universal credit option 'No' is ticked.
            if($postData['reducedFeeUniversalCredit']) {
                $form->setValidationGroup(
                        'reducedFeeUniversalCredit'
                );
            }

            if($form->isValid()) {

                $lpa = $this->getLpa();

                if($form->getData()['reducedFeeUniversalCredit'] == 0) {
                    // not receving universal credit
                    if($form->getData()['reducedFeeLowIncome']) {
                        // has income below 12k, qualify for remission
                        $lpa->payment->reducedFeeUniversalCredit = false;
                        $lpa->payment->reducedFeeLowIncome = true;
                    }
                    else {
                        // income over 12k, full payment to be taken
                        $lpa->payment->reducedFeeUniversalCredit = false;
                        $lpa->payment->reducedFeeLowIncome = false;
                    }
                }
                else {
                    // receive universal credit, no payment required
                    $lpa->payment->reducedFeeUniversalCredit = true;
                    $lpa->payment->date = new \DateTime();
                }

                Calculator::calculate($lpa);

                // persist data
                if(!$this->getLpaApplicationService()->setPayment($lpa->id, $lpa->payment)) {
                    throw new \RuntimeException('API client failed to set income & universal credit in payment for id: '.$lpa->id);
                }

                return $this->moveToNextRoute();
            }
        }
        else {
            if($this->getLpa()->payment instanceof Payment) {
                $form->bind([
                        'reducedFeeLowIncome'       => $this->getLpa()->payment->reducedFeeLowIncome,
                        'reducedFeeUniversalCredit' => $this->getLpa()->payment->reducedFeeUniversalCredit,
                ]);
            }
        }

        return new ViewModel([
                'form'=>$form,
        ]);
    }
}
