<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Zend\View\Model\ViewModel;

class RepeatApplicationController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\RepeatApplicationForm', [
            'lpa' => $lpa,
        ]);

        if ($this->request->isPost()) {
            $postData = $this->request->getPost();

            // set data for validation
            $form->setData($postData);

            if ($postData['isRepeatApplication'] != 'is-repeat') {
                $form->setValidationGroup(
                    'isRepeatApplication'
                );
            }

            if ($form->isValid()) {
                $lpaId = $lpa->id;
                $repeatCaseNumber = $lpa->repeatCaseNumber;

                $lpaApplicationService = $this->getLpaApplicationService();

                // persist data
                if ($form->getData()['isRepeatApplication'] == 'is-repeat') {
                    // set repeat case number only if case number changed or added
                    if ($form->getData()['repeatCaseNumber'] != $lpa->repeatCaseNumber) {
                        if (!$lpaApplicationService->setRepeatCaseNumber($lpa->id, $form->getData()['repeatCaseNumber'])) {
                            throw new \RuntimeException('API client failed to set repeat case number for id: '.$lpaId);
                        }
                    }

                    $lpa->repeatCaseNumber = $form->getData()['repeatCaseNumber'];
                } else {
                    if ($lpa->repeatCaseNumber !== null) {
                        // delete case number if it has been set previousely.
                        if (!$lpaApplicationService->deleteRepeatCaseNumber($lpa->id)) {
                            throw new \RuntimeException('API client failed to set repeat case number for id: '.$lpaId);
                        }
                    }

                    $lpa->repeatCaseNumber = null;
                }

                if ($lpa->payment instanceof Payment && $lpa->repeatCaseNumber != $repeatCaseNumber) {
                    Calculator::calculate($lpa);

                    if (!$lpaApplicationService->setPayment($lpa->id, $lpa->payment)) {
                        throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in RepeatApplicationController');
                    }
                }

                // set metadata
                $this->getServiceLocator()->get('Metadata')->setRepeatApplicationConfirmed($lpa);

                return $this->moveToNextRoute();
            }
        } else {
            if (array_key_exists(Lpa::REPEAT_APPLICATION_CONFIRMED, $lpa->metadata)) {
                $form->bind([
                    'isRepeatApplication' => ($lpa->repeatCaseNumber === null)?'is-new':'is-repeat',
                    'repeatCaseNumber'    => $lpa->repeatCaseNumber,
                ]);
            }
        }

        return new ViewModel([
            'form'         => $form,
            'lpaRepeatFee' => Calculator::getFullFee(true)
        ]);
    }
}
