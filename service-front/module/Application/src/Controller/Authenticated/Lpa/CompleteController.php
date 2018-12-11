<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Common\LongName;
use Zend\View\Model\ViewModel;

class CompleteController extends AbstractLpaController
{
    public function indexAction()
    {
        $this->ensureLpaIsLocked();

        $lpa = $this->getLpa();

        $analyticsDimensions = [];

        if (property_exists($lpa, 'metadata')) {
            if ($lpa->startedAt && $lpa->startedAt instanceof \DateTime) {
                $analyticsDimensions['dimension2'] = $lpa->startedAt->format('Y-m-d');
            }

            if (isset($lpa->metadata['analyticsReturnCount'])) {
                $analyticsDimensions['dimension3'] = $lpa->metadata['analyticsReturnCount'];
            }
        }

        $viewParams = $this->getViewParams();
        $viewParams['analyticsDimensions'] = $analyticsDimensions;

        $viewModel = new ViewModel($viewParams);
        $viewModel->setTemplate('application/authenticated/lpa/complete/complete.twig');

        return $viewModel;
    }

    public function viewDocsAction()
    {
        $this->ensureLpaIsLocked();

        return new ViewModel($this->getViewParams());
    }

    /**
     * Ensure the LPA is always locked by this stage.
     */
    private function ensureLpaIsLocked()
    {
        $lpa = $this->getLpa();

        if ($lpa->locked !== true) {
            $this->getLpaApplicationService()->lockLpa($lpa);
        }
    }

    private function getViewParams()
    {
        $lpa = $this->getLpa();

        $payment = $this->getLpa()->payment;

        $isPaymentSkipped = ($payment->reducedFeeUniversalCredit === true
            || ($payment->reducedFeeReceivesBenefits === true && $payment->reducedFeeAwardedDamages === true)
            || $payment->method == Payment::PAYMENT_TYPE_CHEQUE);

        //Array of keys to know which extra notes to show in template for continuation sheets
        $continuationNotes = array();

        $extraBlocksPeople = $this->getExtraBlocksPeople();

        if($extraBlocksPeople) array_push($extraBlocksPeople, $continuationNotes);

        if(!$lpa->document->donor->canSign) array_push('CANT_SIGN', $continuationNotes);

        $someAttorneyIsTrustCorp = false;


        foreach($lpa->document->primaryAttorneys as $attorney) {
            if(isset($attorney->number)) $someAttorneyIsTrustCorp = true;
        }

        if($someAttorneyIsTrustCorp) array_push('HAS_TRUST_CORP', $continuationNotes);

        $viewParams = [
            'lp1Url'             => $this->url()->fromRoute('lpa/download', ['lpa-id' => $lpa->id, 'pdf-type' => 'lp1']),
            'cloneUrl'           => $this->url()->fromRoute('user/dashboard/create-lpa', ['lpa-id' => $lpa->id]),
            'dateCheckUrl'       => $this->url()->fromRoute('lpa/date-check/complete', ['lpa-id' => $lpa->id]),
            'correspondentName'  => ($lpa->document->correspondent->name instanceof LongName ? $lpa->document->correspondent->name : $lpa->document->correspondent->company),
            'paymentAmount'      => $lpa->payment->amount,
            'paymentReferenceNo' => $lpa->payment->reference,
            'hasRemission'       => $lpa->isEligibleForFeeReduction(),
            'isPaymentSkipped'   => $isPaymentSkipped,
            'continuationNotes'   => $continuationNotes,
        ];

        if (count($lpa->document->peopleToNotify) > 0) {
            $viewParams['lp3Url'] = $this->url()->fromRoute('lpa/download', ['lpa-id' => $lpa->id, 'pdf-type' => 'lp3']);
            $viewParams['peopleToNotify'] = $lpa->document->peopleToNotify;
        }

        if ($lpa->isEligibleForFeeReduction()) {
            $viewParams['lpa120Url'] = $this->url()->fromRoute('lpa/download', ['lpa-id' => $lpa->id, 'pdf-type' => 'lpa120']);
        }

        return $viewParams;
    }

    //Get note keys referred to in templates for continuation sheet information regarding people. Separated to use early return instead of nesting.
    private function getExtraBlocksPeople() {

        $lpa = $this->getLpa();

        $paCount = count($lpa->document->primaryAttorneys);
        $raCount = count($lpa->document->replacementAttorneys);
        $pnCount = count($lpa->document->peopleToNotify);
        
        if(paCount > 4 && raCount > 2 &&  pnCount > 4) {
            return 'ALL_PEOPLE_OVERFLOW';
        }

        if(paCount > 4 && raCount > 2) {
            return 'ALL_ATTORNEY_OVERFLOW';
        } 

        if(paCount > 4 && pnCount > 4) {
            return 'PRIMARY_ATTORNEY_AND_NOTIFY_OVERFLOW';
        }               

        if(raCount > 2 &&  pnCount > 4) {
            return 'REPLACEMENT_ATTORNEY_AND_NOTIFY_OVERFLOW';
        }

        if(paCount > 4) {
            return 'PRIMARY_ATTORNEY_OVERFLOW';
        }  

        if(raCount > 2) {
            return 'REPLACEMENT_ATTORNEY_OVERFLOW';
        }

        if(pnCount > 4) {
            return 'NOTIFY_OVERFLOW';
        }

        return false;
    }
}
