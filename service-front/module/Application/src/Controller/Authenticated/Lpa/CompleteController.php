<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractAuthenticatedController;
use Application\Listener\LpaLoaderTrait;
use Application\Model\Service\Lpa\ContinuationSheets;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\DataModel\Common\LongName;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;

class CompleteController extends AbstractAuthenticatedController
{
    use LoggerTrait;
    use LpaLoaderTrait;

    public function indexAction()
    {
        $this->ensureLpaIsLocked();

        $lpa = $this->getLpa();

        $viewParams = $this->getViewParams();
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

        $continuationSheets = new ContinuationSheets();
        $continuationNoteKeys = $continuationSheets->getContinuationNoteKeys($lpa);

        $viewParams = [
            'lp1Url' => $this->url()->fromRoute('lpa/download', ['lpa-id' => $lpa->id, 'pdf-type' => 'lp1']),
            'cloneUrl' => $this->url()->fromRoute('user/dashboard/create-lpa', ['lpa-id' => $lpa->id]),
            'dateCheckUrl' => $this->url()->fromRoute('lpa/date-check/complete', ['lpa-id' => $lpa->id]),
            'continuationNoteKeys' => $continuationNoteKeys,
            'correspondentName' => ($lpa->document->correspondent->name instanceof LongName ?
                $lpa->document->correspondent->name : $lpa->document->correspondent->company),
            'paymentAmount' => $lpa->payment->amount,
            'paymentReferenceNo' => $lpa->payment->reference,
            'hasRemission' => $lpa->isEligibleForFeeReduction(),
            'isPaymentSkipped' => $isPaymentSkipped,
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
}
