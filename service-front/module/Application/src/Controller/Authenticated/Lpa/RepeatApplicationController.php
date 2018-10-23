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

        $form = $this->getFormElementManager()->get('Application\Form\Lpa\RepeatApplicationForm', [
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
                $formData = $form->getData();

                $repeatCaseNumber = $lpa->repeatCaseNumber;

                // persist data
                if ($formData['isRepeatApplication'] == 'is-repeat') {
                    // set repeat case number only if case number changed or added
                    if ($formData['repeatCaseNumber'] != $lpa->repeatCaseNumber) {
                        if (!$this->getLpaApplicationService()->setRepeatCaseNumber($lpa, $formData['repeatCaseNumber'])) {
                            throw new \RuntimeException('API client failed to set repeat case number for id: ' . $lpa->id);
                        }
                    }

                    $lpa->repeatCaseNumber = $formData['repeatCaseNumber'];
                } else {
                    if ($lpa->repeatCaseNumber !== null) {
                        // delete case number if it has been set previousely.
                        if (!$this->getLpaApplicationService()->deleteRepeatCaseNumber($lpa)) {
                            throw new \RuntimeException('API client failed to set repeat case number for id: ' . $lpa->id);
                        }
                    }

                    $lpa->repeatCaseNumber = null;
                }

                if ($lpa->payment instanceof Payment && $lpa->repeatCaseNumber != $repeatCaseNumber) {
                    Calculator::calculate($lpa);

                    if (!$this->getLpaApplicationService()->setPayment($lpa, $lpa->payment)) {
                        throw new \RuntimeException('API client failed to set payment details for id: '.$lpa->id . ' in RepeatApplicationController');
                    }
                }

                // set metadata
                $this->getMetadata()->setRepeatApplicationConfirmed($lpa);

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
            'form' => $form,
        ]);
    }
}
