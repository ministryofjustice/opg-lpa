<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Application\Model\Service\Lpa\ContinuationSheets;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Common\LongName;
use Laminas\View\Model\ViewModel;

class CompleteController extends AbstractLpaController
{
    /** @var ContinuationSheets */
    private $continuationSheets;

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

        $continuationNoteKeys = $this->continuationSheets->getContinuationNoteKeys($lpa);

        $viewParams = [
            'lp1Url' => $this->url()->fromRoute('lpa/download', ['lpa-id' => $lpa->id, 'pdf-type' => 'lp1']),
            'cloneUrl' => $this->url()->fromRoute('user/dashboard/create-lpa', ['lpa-id' => $lpa->id]),
            'dateCheckUrl' => $this->url()->fromRoute('lpa/date-check/complete', ['lpa-id' => $lpa->id]),
            'correspondentName' => ($lpa->document->correspondent->name instanceof LongName ?
                $lpa->document->correspondent->name : $lpa->document->correspondent->company),
            'paymentAmount' => $lpa->payment->amount,
            'paymentReferenceNo' => $lpa->payment->reference,
            'hasRemission' => $lpa->isEligibleForFeeReduction(),
            'isPaymentSkipped' => $isPaymentSkipped,
            'continuationNoteKeys' => $continuationNoteKeys,
        ];

        if (count($lpa->document->peopleToNotify) > 0) {
            $viewParams['lp3Url'] = $this->url()->fromRoute(
                'lpa/download',
                ['lpa-id' => $lpa->id, 'pdf-type' => 'lp3']
            );
            $viewParams['peopleToNotify'] = $lpa->document->peopleToNotify;
        }

        if ($lpa->isEligibleForFeeReduction()) {
            $viewParams['lpa120Url'] = $this->url()->fromRoute(
                'lpa/download',
                ['lpa-id' => $lpa->id, 'pdf-type' => 'lpa120']
            );
        }

        return $viewParams;
    }


    public function setContinuationSheets(ContinuationSheets $continuationSheets)
    {
        $this->continuationSheets = $continuationSheets;
    }
}
