<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ContinuationSheets;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Payment;

class CompleteViewParamsHelper
{
    public function __construct(
        private readonly MvcUrlHelper $urlHelper,
        private readonly ContinuationSheets $continuationSheets,
    ) {
    }

    public function build(Lpa $lpa): array
    {
        $payment = $lpa->payment;

        $isPaymentSkipped = ($payment->reducedFeeUniversalCredit === true
            || ($payment->reducedFeeReceivesBenefits === true && $payment->reducedFeeAwardedDamages === true)
            || $payment->method == Payment::PAYMENT_TYPE_CHEQUE);

        $viewParams = [
            'lp1Url' => $this->urlHelper->generate(
                'lpa/download',
                ['lpa-id' => $lpa->id, 'pdf-type' => 'lp1']
            ),
            'cloneUrl' => $this->urlHelper->generate(
                'user/dashboard/create-lpa',
                ['lpa-id' => $lpa->id]
            ),
            'dateCheckUrl' => $this->urlHelper->generate(
                'lpa/date-check/complete',
                ['lpa-id' => $lpa->id]
            ),
            'continuationNoteKeys' => $this->continuationSheets->getContinuationNoteKeys($lpa),
            'correspondentName' => ($lpa->document->correspondent->name instanceof LongName
                ? $lpa->document->correspondent->name
                : $lpa->document->correspondent->company),
            'paymentAmount' => $lpa->payment->amount,
            'paymentReferenceNo' => $lpa->payment->reference,
            'hasRemission' => $lpa->isEligibleForFeeReduction(),
            'isPaymentSkipped' => $isPaymentSkipped,
        ];

        if (count($lpa->document->peopleToNotify) > 0) {
            $viewParams['lp3Url'] = $this->urlHelper->generate(
                'lpa/download',
                ['lpa-id' => $lpa->id, 'pdf-type' => 'lp3']
            );
            $viewParams['peopleToNotify'] = $lpa->document->peopleToNotify;
        }

        if ($lpa->isEligibleForFeeReduction()) {
            $viewParams['lpa120Url'] = $this->urlHelper->generate(
                'lpa/download',
                ['lpa-id' => $lpa->id, 'pdf-type' => 'lpa120']
            );
        }

        return $viewParams;
    }
}
