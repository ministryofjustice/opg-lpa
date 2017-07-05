<?php

namespace Application\Model;

use Application\Model\Service\Lpa\Metadata;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Opg\Lpa\DataModel\Lpa\StateChecker;

class FormFlowChecker extends StateChecker
{

    static $accessibleFunctionMap = array(
        'lpa'                                           => 'isLpaAccessible',
        'lpa/form-type'                                 => 'isFormTypeAccessible',
        'lpa-type-no-id'                                => 'isFormTypeAccessible',
        'lpa/donor'                                     => 'isDonorAccessible',
        'lpa/donor/add'                                 => 'isDonorAddAccessible',
        'lpa/donor/edit'                                => 'isDonorEditAccessible',
        'lpa/when-lpa-starts'                           => 'isWhenLpaStartsAccessible',
        'lpa/life-sustaining'                           => 'isLifeSustainingAccessible',
        'lpa/primary-attorney'                          => 'isAttorneyAccessible',
        'lpa/primary-attorney/add'                      => 'isAttorneyAddAccessible',
        'lpa/primary-attorney/edit'                     => 'isAttorneyEditAccessible',
        'lpa/primary-attorney/confirm-delete'           => 'isAttorneyDeleteAccessible',
        'lpa/primary-attorney/delete'                   => 'isAttorneyDeleteAccessible',
        'lpa/primary-attorney/add-trust'                => 'isAttorneyAddTrustAccessible',
        'lpa/how-primary-attorneys-make-decision'       => 'isHowPrimaryAttorneysMakeDecisionAccessible',
        'lpa/replacement-attorney'                      => 'isReplacementAttorneyAccessible',
        'lpa/replacement-attorney/add'                  => 'isReplacementAttorneyAddAccessible',
        'lpa/replacement-attorney/edit'                 => 'isReplacementAttorneyEditAccessible',
        'lpa/replacement-attorney/confirm-delete'       => 'isReplacementAttorneyDeleteAccessible',
        'lpa/replacement-attorney/delete'               => 'isReplacementAttorneyDeleteAccessible',
        'lpa/replacement-attorney/add-trust'            => 'isReplacementAttorneyAddTrustAccessible',
        'lpa/when-replacement-attorney-step-in'         => 'isWhenReplacementAttorneyStepInAccessible',
        'lpa/how-replacement-attorneys-make-decision'   => 'isHowReplacementAttorneysMakeDecisionAccessible',
        'lpa/certificate-provider'                      => 'isCertificateProviderAccessible',
        'lpa/certificate-provider/add'                  => 'isCertificateProviderAddAccessible',
        'lpa/certificate-provider/edit'                 => 'isCertificateProviderEditAccessible',
        'lpa/people-to-notify'                          => 'isPeopleToNotifyAccessible',
        'lpa/people-to-notify/add'                      => 'isPeopleToNotifyAddAccessible',
        'lpa/people-to-notify/edit'                     => 'isPeopleToNotifyEditAccessible',
        'lpa/people-to-notify/confirm-delete'           => 'isPeopleToNotifyDeleteAccessible',
        'lpa/people-to-notify/delete'                   => 'isPeopleToNotifyDeleteAccessible',
        'lpa/instructions'                              => 'isInstructionsAccessible',
        'lpa/download'                                  => 'isDownloadAccessible',
        'lpa/download/draft'                            => 'isDownloadAccessible',
        'lpa/download/file'                             => 'isDownloadAccessible',
        'lpa/applicant'                                 => 'isApplicantAccessible',
        'lpa/correspondent'                             => 'isCorrespondentAccessible',
        'lpa/correspondent/edit'                        => 'isCorrespondentEditAccessible',
        'lpa/who-are-you'                               => 'isWhoAreYouAccessible',
        'lpa/repeat-application'                        => 'isRepeatApplicationAccessible',
        'lpa/fee-reduction'                             => 'isFeeReductionAccessible',
        'lpa/checkout'                                  => 'isPaymentAccessible',
        'lpa/checkout/cheque'                           => 'isPaymentAccessible',
        'lpa/checkout/pay'                              => 'isPaymentAccessible',
        'lpa/checkout/pay/response'                     => 'isPaymentAccessible',
        'lpa/checkout/confirm'                          => 'isPaymentAccessible',
        'lpa/checkout/worldpay'                         => 'isPaymentAccessible',
        'lpa/checkout/worldpay/return'                  => 'isPaymentAccessible',
        'lpa/checkout/worldpay/return/success'          => 'isOnlinePaymentSuccessAccessible',
        'lpa/checkout/worldpay/return/failure'          => 'isOnlinePaymentFailureAccessible',
        'lpa/checkout/worldpay/return/cancel'           => 'isOnlinePaymentCancelAccessible',
        'lpa/complete'                                  => 'isCompleteAccessible',
        'lpa/more-info-required'                        => 'isMoreInfoRequiredAccessible',
        'lpa/date-check'                                => 'isApplicantAccessible',
        'lpa/date-check/complete'                       => 'isCompleteAccessible',
        'lpa/date-check/valid'                          => 'isApplicantAccessible',
        'lpa/summary'                                   => 'isInstructionsAccessible',
        'lpa/view-docs'                                 => 'isViewDocsAccessible',
        'lpa/reuse-details'                             => 'isReuseDetailsAccessible',
    );

    static $returnFunctionMap = array(
        'lpa/form-type'                                 => 'returnToFormType',
        'lpa/donor'                                     => 'returnToDonor',
        'lpa/when-lpa-starts'                           => 'returnToWhenLpaStarts',
        'lpa/life-sustaining'                           => 'returnToLifeSustaining',
        'lpa/primary-attorney'                          => 'returnToPrimaryAttorney',
        'lpa/how-primary-attorneys-make-decision'       => 'returnToHowPrimaryAttorneysMakeDecision',
        'lpa/replacement-attorney'                      => 'returnToReplacementAttorney',
        'lpa/when-replacement-attorney-step-in'         => 'returnToWhenReplacementAttorneyStepIn',
        'lpa/how-replacement-attorneys-make-decision'   => 'returnToHowReplacementAttorneysMakeDecision',
        'lpa/certificate-provider'                      => 'returnToCertificateProvider',
        'lpa/people-to-notify'                          => 'returnToPeopleToNotify',
        'lpa/instructions'                              => 'returnToInstructions',
        'lpa/applicant'                                 => 'returnToApplicant',
        'lpa/correspondent'                             => 'returnToCorrespondent',
        'lpa/who-are-you'                               => 'returnToWhoAreYou',
        'lpa/repeat-application'                        => 'returnToRepeatApplication',
        'lpa/fee-reduction'                             => 'returnToFeeReduction',
        'lpa/checkout'                                  => 'returnToCheckout',
        'lpa/view-docs'                                 => 'returnToViewDocs',
    );

    static $nextRouteMap = array(
        'lpa-type-no-id'                                => 'lpa/donor',
        'lpa/form-type'                                 => 'lpa/donor',
        'lpa/donor'                                     => ['lpa/when-lpa-starts', 'lpa/life-sustaining'],
        'lpa/donor/add'                                 => 'lpa/donor',
        'lpa/donor/edit'                                => 'lpa/donor',
        'lpa/when-lpa-starts'                           => 'lpa/primary-attorney',
        'lpa/life-sustaining'                           => 'lpa/primary-attorney',
        'lpa/primary-attorney'                          => ['lpa/how-primary-attorneys-make-decision', 'lpa/replacement-attorney'],
        'lpa/primary-attorney/add'                      => 'lpa/primary-attorney',
        'lpa/primary-attorney/edit'                     => 'lpa/primary-attorney',
        'lpa/primary-attorney/delete'                   => 'lpa/primary-attorney',
        'lpa/primary-attorney/add-trust'                => 'lpa/primary-attorney',
        'lpa/how-primary-attorneys-make-decision'       => 'lpa/replacement-attorney',
        'lpa/replacement-attorney'                      => ['lpa/when-replacement-attorney-step-in', 'lpa/how-replacement-attorneys-make-decision', 'lpa/how-replacement-attorneys-make-decision', 'lpa/certificate-provider'],
        'lpa/replacement-attorney/add'                  => 'lpa/replacement-attorney',
        'lpa/replacement-attorney/edit'                 => 'lpa/replacement-attorney',
        'lpa/replacement-attorney/delete'               => 'lpa/replacement-attorney',
        'lpa/replacement-attorney/add-trust'            => 'lpa/replacement-attorney',
        'lpa/when-replacement-attorney-step-in'         => ['lpa/how-replacement-attorneys-make-decision','lpa/certificate-provider'],
        'lpa/how-replacement-attorneys-make-decision'   => 'lpa/certificate-provider',
        'lpa/certificate-provider'                      => 'lpa/people-to-notify',
        'lpa/certificate-provider/add'                  => 'lpa/certificate-provider',
        'lpa/certificate-provider/edit'                 => 'lpa/certificate-provider',
        'lpa/people-to-notify'                          => 'lpa/instructions',
        'lpa/people-to-notify/add'                      => 'lpa/people-to-notify',
        'lpa/people-to-notify/edit'                     => 'lpa/people-to-notify',
        'lpa/people-to-notify/delete'                   => 'lpa/people-to-notify',
        'lpa/instructions'                              => 'lpa/applicant',
        'lpa/applicant'                                 => 'lpa/correspondent',
        'lpa/correspondent'                             => 'lpa/who-are-you',
        'lpa/correspondent/edit'                        => 'lpa/correspondent',
        'lpa/who-are-you'                               => 'lpa/repeat-application',
        'lpa/repeat-application'                        => 'lpa/fee-reduction',
        'lpa/fee-reduction'                             => 'lpa/checkout',
        'lpa/payment'                                   => 'lpa/payment/summary',
        'lpa/payment/summary'                           => 'lpa/complete',
        'lpa/checkout/cheque'                           => 'lpa/complete',
        'lpa/checkout/confirm'                          => 'lpa/complete',
        'lpa/checkout/pay/response'                     => 'lpa/complete',
        'lpa/checkout/worldpay/return/success'          => 'lpa/complete',
    );

    /**
     * For given route, work out the latest accessible route.
     *
     * @param string $currentRouteName - a route name
     * @param mixed $param - person-idx or pdf-type
     * @throws \RuntimeException
     * @return string - a route name
     */
    public function getNearestAccessibleRoute($currentRouteName, $param=null)
    {
        //  If there is no LPA then just return to the dashboard
        if (!$this->lpa instanceof Lpa) {
            return 'user/dashboard';
        }

        // check if route exists in the mapping table.
        if(!array_key_exists($currentRouteName, static::$accessibleFunctionMap)) {
            throw new \RuntimeException('Check() received an undefined route: '. $currentRouteName);
        }

        // Once an LPA has been locked, only allow the following pages.
        if(!empty($this->lpa) && ( $this->lpa->locked === true )
            && ($currentRouteName != 'lpa/complete')
            && (strpos($currentRouteName, 'lpa/date-check') === false)
            && ($currentRouteName != 'lpa/download')
            && ($currentRouteName != 'lpa/download/file') ) {
                return 'lpa/view-docs';
        }

        $checkFunction = static::$accessibleFunctionMap[$currentRouteName];

        $checkValue = call_user_func(array($this, $checkFunction), $param);

        if($checkValue === true) {
            return $currentRouteName;
        }
        else {
            if(array_key_exists($checkValue, static::$accessibleFunctionMap)) {
                return $this->getNearestAccessibleRoute($checkValue);
            }
            else {
                return $checkValue;
            }
        }
    }

    public function nextRoute($currentRouteName, $personIdex=null)
    {
        if(array_key_exists($currentRouteName, static::$nextRouteMap)) {
            if(is_array(static::$nextRouteMap[$currentRouteName])) {
                foreach(static::$nextRouteMap[$currentRouteName] as $nextRoute) {
                    if($this->getNearestAccessibleRoute($nextRoute, $personIdex) == $nextRoute) {
                        return $nextRoute;
                    }
                }
            }
            else {
                return static::$nextRouteMap[$currentRouteName];
            }
        }

        return $currentRouteName;
    }

    public function backToForm()
    {
        $lastValidRoute = "";

        foreach (static::$returnFunctionMap as $route => $fn) {
            $canAccess = call_user_func(array($this, $fn));
            if ($canAccess === false) {
                break;
            }
            if ($canAccess === true) {
                $lastValidRoute = $route;
            }
        }

        return $lastValidRoute;
    }

###################  Private methods - accessible methods #################################################

    private function isLpaAccessible()
    {
        if($this->lpaHasDocument()) {
            return true;
        }
        else {
            return 'user/dashboard';
        }
    }

    private function isFormTypeAccessible()
    {
        if($this->lpaHasDocument()) {
            return true;
        }
        else {
            return 'user/dashboard';
        }
    }

    private function isDonorAccessible()
    {
        if($this->lpaHasType()) {
            return true;
        }
        else {
            return 'lpa/form-type';
        }
    }

    private function isDonorAddAccessible()
    {
        return $this->isDonorAccessible();
    }

    private function isDonorEditAccessible()
    {
        if($this->lpaHasDonor()) {
            return true;
        }
        else {
            return 'lpa/donor';
        }
    }

    private function isLifeSustainingAccessible()
    {
        if($this->lpaHasDonor() && ($this->lpa->document->type == Document::LPA_TYPE_HW)) {
            return true;
        }
        else {
            return 'lpa/donor';
        }
    }

    private function isWhenLpaStartsAccessible()
    {
        if($this->lpaHasDonor() && ($this->lpa->document->type == Document::LPA_TYPE_PF)) {
            return true;
        }
        else {
            return 'lpa/donor';
        }
    }

    private function isAttorneyAccessible()
    {
        if($this->lpaHasType()) {
            if($this->lpa->document->type == Document::LPA_TYPE_PF) {
                if($this->lpaHasWhenLpaStarts()) {
                    return true;
                }
                else {
                    return 'lpa/when-lpa-starts';
                }
            }
            else {
                if($this->lpaHasLifeSustaining()) {
                    return true;
                }
                else {
                    return 'lpa/life-sustaining';
                }
            }
        }
        else {
            return 'lpa/form-type';
        }
    }

    private function isAttorneyAddAccessible()
    {
        if($this->isAttorneyAccessible() === true) {
            return true;
        }
        else {
            return 'lpa/primary-attorney';
        }
    }

    private function isAttorneyEditAccessible($idx)
    {
        if(($this->isAttorneyAccessible() === true) && $this->lpaHasPrimaryAttorney($idx)) {
            return true;
        }
        else {
            return 'lpa/primary-attorney';
        }
    }

    private function isAttorneyDeleteAccessible($idx)
    {
        if(($this->isAttorneyAccessible() === true) && $this->lpaHasPrimaryAttorney($idx)) {
            return true;
        }
        else {
            return 'lpa/primary-attorney';
        }
    }

    private function isAttorneyAddTrustAccessible()
    {
        if(($this->isAttorneyAccessible() === true) && (!$this->lpaHasTrustCorporation('primary'))) {
            return true;
        }
        else {
            return 'lpa/primary-attorney';
        }
    }

    private function isHowPrimaryAttorneysMakeDecisionAccessible()
    {
        if($this->lpaHasMultiplePrimaryAttorneys()) {
            return true;
        }
        else {
            return 'lpa/primary-attorney';
        }
    }

    private function isReplacementAttorneyAccessible()
    {
        if($this->lpaHasMultiplePrimaryAttorneys()) {
            if($this->lpaHowPrimaryAttorneysMakeDecisionHasValue()) {
                return true;
            }
            else {
                return 'lpa/how-primary-attorneys-make-decision';
            }
        }
        elseif($this->lpaHasPrimaryAttorney()) {
            return true;
        }

        return 'lpa/primary-attorney';
    }

    private function isReplacementAttorneyAddAccessible()
    {
        if($this->isReplacementAttorneyAccessible() === true) {
            return true;
        }
        else {
            return 'lpa/replacement-attorney';
        }
    }

    private function isReplacementAttorneyEditAccessible($idx)
    {
        if(($this->isReplacementAttorneyAccessible() === true) && $this->lpaHasReplacementAttorney($idx)) {
            return true;
        }
        else {
            return 'lpa/replacement-attorney';
        }
    }

    private function isReplacementAttorneyDeleteAccessible($idx)
    {
        if(($this->isReplacementAttorneyAccessible() === true) && $this->lpaHasReplacementAttorney($idx)) {
            return true;
        }
        else {
            return 'lpa/replacement-attorney';
        }
    }

    private function isReplacementAttorneyAddTrustAccessible()
    {
        if(($this->isReplacementAttorneyAccessible() === true) && (!$this->lpaHasTrustCorporation('replacement'))) {
            return true;
        }
        else {
            return 'lpa/replacement-attorney';
        }
    }

    private function isWhenReplacementAttorneyStepInAccessible()
    {
        if($this->lpaHasReplacementAttorney() && $this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
            return true;
        }
        else {
            return 'lpa/replacement-attorney';
        }
    }

    private function isHowReplacementAttorneysMakeDecisionAccessible()
    {
        if($this->lpaHasMultipleReplacementAttorneys()) {
            if((count($this->lpa->document->primaryAttorneys) == 1)
              ||($this->lpaReplacementAttorneyStepInWhenLastPrimaryUnableAct())
              ||($this->lpaPrimaryAttorneysMakeDecisionJointly())
                ) {
                    return true;
            }
            else {
                if($this->lpaHasMultiplePrimaryAttorneys()
                    && $this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
                    return 'lpa/when-replacement-attorney-step-in';
                }
                else {
                    return 'lpa/replacement-attorney';
                }
            }
        }
        else {
            return 'lpa/replacement-attorney';
        }
    }

    private function isCertificateProviderAccessible()
    {
        if($this->lpaHasPrimaryAttorney() && (
               ($this->lpaHasSinglePrimaryAttorney() && $this->lpaHasNoReplacementAttorney() && array_key_exists(Metadata::REPLACEMENT_ATTORNEYS_CONFIRMED, $this->lpa->metadata))
            || ($this->lpaHasSinglePrimaryAttorney() && $this->lpaHasSingleReplacementAttorney())
            || ($this->lpaHasSinglePrimaryAttorney() && $this->lpaHasMultipleReplacementAttorneys() && $this->lpaHowReplacementAttorneysMakeDecisionHasValue())
            || ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaHowPrimaryAttorneysMakeDecisionHasValue() && $this->lpaHasNoReplacementAttorney() && array_key_exists(Metadata::REPLACEMENT_ATTORNEYS_CONFIRMED, $this->lpa->metadata))
            || ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionDepends() && $this->lpaHasMultipleReplacementAttorneys())
            || ($this->lpaHasMultiplePrimaryAttorneys() && ($this->lpaPrimaryAttorneysMakeDecisionJointly() || $this->lpaPrimaryAttorneysMakeDecisionDepends()) && $this->lpaHasSingleReplacementAttorney())
            || ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointly() && $this->lpaHasMultipleReplacementAttorneys() && $this->lpaHowReplacementAttorneysMakeDecisionHasValue())
            || ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally() && $this->lpaHasSingleReplacementAttorney() && $this->lpaWhenReplacementAttorneyStepInHasValue())
            || ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally() && $this->lpaHasMultipleReplacementAttorneys() && $this->lpaWhenReplacementAttorneyStepInHasValue() && ($this->lpa->document->replacementAttorneyDecisions->when != ReplacementAttorneyDecisions::LPA_DECISION_WHEN_LAST))
            || ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally() && $this->lpaHasMultipleReplacementAttorneys() && $this->lpaReplacementAttorneyStepInWhenLastPrimaryUnableAct() && $this->lpaHowReplacementAttorneysMakeDecisionHasValue())
        )) {
            return true;
        }
        else {
            if($this->lpaHasMultipleReplacementAttorneys()
                && ((count($this->lpa->document->primaryAttorneys)==1)
                    || ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointly())
                    || ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally() && $this->lpaReplacementAttorneyStepInWhenLastPrimaryUnableAct())
                )
                && (!$this->lpaHowReplacementAttorneysMakeDecisionHasValue() || ($this->lpa->document->replacementAttorneyDecisions->how == null)))
                {
                return 'lpa/how-replacement-attorneys-make-decision';
            }
            elseif($this->lpaHasReplacementAttorney() && $this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
                return 'lpa/when-replacement-attorney-step-in';
            }
            else{
                return 'lpa/replacement-attorney';
            }
        }
    }

    private function isCertificateProviderAddAccessible()
    {
        if($this->isCertificateProviderAccessible() === true) {
            return true;
        }
        else {
            return 'lpa/certificate-provider';
        }
    }

    private function isCertificateProviderEditAccessible()
    {
        if(($this->isCertificateProviderAccessible() === true) && $this->lpaHasCertificateProvider()) {
            return true;
        }
        else {
            return 'lpa/certificate-provider';
        }
    }

    private function isPeopleToNotifyAccessible()
    {
        if(($this->isCertificateProviderAccessible() === true) && $this->lpaHasCertificateProvider()) {
            return true;
        }
        else {
            return 'lpa/certificate-provider';
        }
    }

    private function isPeopleToNotifyAddAccessible()
    {
        if($this->isPeopleToNotifyAccessible() === true) {
            return true;
        }
        else {
            return 'lpa/people-to-notify';
        }
    }

    private function isPeopleToNotifyEditAccessible($idx)
    {
        if(($this->isPeopleToNotifyAccessible() === true) && $this->lpaHasPeopleToNotify($idx)) {
            return true;
        }
        else {
            return 'lpa/people-to-notify';
        }
    }

    private function isPeopleToNotifyDeleteAccessible($idx)
    {
        if(($this->isPeopleToNotifyAccessible() === true) && $this->lpaHasPeopleToNotify($idx)) {
            return true;
        }
        else {
            return 'lpa/people-to-notify';
        }
    }

    private function isInstructionsAccessible()
    {
        if(($this->isPeopleToNotifyAccessible() === true) && $this->peopleToNotifyHasBeenConfirmed()) {
            return true;
        }
        else {
            return 'lpa/people-to-notify';
        }
    }

    // accessibility is checked in controller, and will not be redirected when not available.
    private function isDownloadAccessible($pdfType)
    {
        return true;
    }

    private function isApplicantAccessible()
    {
        if($this->lpaHasCreated()) {
            return true;
        }
        else {
            return 'lpa/instructions';
        }
    }

    private function isCorrespondentAccessible()
    {
        if($this->lpaHasApplicant()) {
            return true;
        }
        else {
            return 'lpa/applicant';
        }
    }

    private function isCorrespondentEditAccessible()
    {
        if($this->lpaHasApplicant()) {
            return true;
        }
        else {
            return 'lpa/applicant';
        }
    }

    private function isWhoAreYouAccessible()
    {
        if($this->lpaHasCorrespondent()) {
            return true;
        }
        else {
            return 'lpa/correspondent';
        }
    }

    private function isRepeatApplicationAccessible()
    {
        if($this->isWhoAreYouAnswered()) {
            return true;
        }
        else {
            return 'lpa/who-are-you';
        }
    }

    private function isFeeReductionAccessible()
    {
        if( $this->isRepeatApplicationAccessible() === true && array_key_exists(Metadata::REPEAT_APPLICATION_CONFIRMED, $this->lpa->metadata)) {
            return true;
        }
        else {
            return 'lpa/repeat-application';
        }
    }

    private function isPaymentAccessible()
    {
        if($this->lpa->payment instanceof Payment) {
            return true;
        }
        else {
            return 'lpa/fee-reduction';
        }
    }

    private function isOnlinePaymentSuccessAccessible()
    {
        if($this->isPaymentAccessible() === true &&
                ($this->lpa->payment->method == 'card')) {
            return true;
        }
        else {
            return 'lpa/checkout';
        }
    }

    private function isOnlinePaymentFailureAccessible()
    {
        if($this->isPaymentAccessible() === true &&
                ($this->lpa->payment->method == 'card')) {
            return true;
        }
        else {
            return 'lpa/checkout';
        }
    }

    private function isOnlinePaymentCancelAccessible()
    {
        if($this->isPaymentAccessible() === true &&
                ($this->lpa->payment->method == 'card')) {
            return true;
        }
        else {
            return 'lpa/checkout';
        }
    }

    private function isCompleteAccessible()
    {
        if($this->paymentResolved()) {
            return true;
        }
        else {
            return 'lpa/checkout';
        }
    }

    private function isMoreInfoRequiredAccessible()
    {
        return true;
    }
    private function isViewDocsAccessible()
    {
        if($this->paymentResolved() && ($this->lpa->completedAt !== null)) {
            return true;
        }
        else {
            return 'lpa/checkout';
        }
    }

    private function isReuseDetailsAccessible()
    {
        return true;
    }

    private function peopleToNotifyHasBeenConfirmed()
    {
        return ($this->lpaHasCertificateProvider() &&
                (count($this->lpa->document->peopleToNotify) > 0) || array_key_exists(Metadata::PEOPLE_TO_NOTIFY_CONFIRMED, $this->lpa->metadata));
    }

    protected function replacementAttorneyHasBeenConfirmed()
    {
        return ($this->lpaHasPrimaryAttorney() &&
                (count($this->lpa->document->replacementAttorneys) > 0) || array_key_exists(Metadata::REPLACEMENT_ATTORNEYS_CONFIRMED, $this->lpa->metadata));
    }

    protected function lpaHasSingleReplacementAttorney()
    {
        return ($this->lpaHasReplacementAttorney() && (count($this->lpa->document->replacementAttorneys) == 1));
    }

    protected function lpaHasNoReplacementAttorney()
    {
        return (count($this->lpa->document->replacementAttorneys) == 0);
    }

    protected function lpaHasSinglePrimaryAttorney()
    {
        return ($this->lpaHasPrimaryAttorney() && (count($this->lpa->document->primaryAttorneys) == 1));
    }

    protected function lpaHasNoPrimaryAttorney()
    {
        return (count($this->lpa->document->primaryAttorneys)==0);
    }

######################## return functions #####################

    private function returnToNewLpa()
    {
        return true;
    }

    private function returnToFormType()
    {
        return true;
    }

    private function returnToDonor()
    {
        return $this->lpaHasDonor();
    }

    private function returnToLifeSustaining()
    {
        if ($this->lpa->document->type != Document::LPA_TYPE_HW) {
            return 'NA';
        }
        return $this->lpaHasLifeSustaining();
    }

    private function returnToWhenLpaStarts()
    {
        if ($this->lpa->document->type != Document::LPA_TYPE_PF) {
            return 'NA';
        }
        return $this->lpaHasWhenLpaStarts();
    }

    private function returnToPrimaryAttorney()
    {
        return $this->lpaHasPrimaryAttorney();
    }

    private function returnToHowPrimaryAttorneysMakeDecision()
    {
        // only required if there are multiple primary attorneys
        if(!$this->lpaHasMultiplePrimaryAttorneys()) {
            return "NA";
        }
        return $this->lpaHowPrimaryAttorneysMakeDecisionHasValue();
    }

    private function returnToReplacementAttorney()
    {
        return $this->replacementAttorneyHasBeenConfirmed();
    }

    private function returnToWhenReplacementAttorneyStepIn()
    {
        if(!$this->lpaHasReplacementAttorney() || !$this->lpaHasMultiplePrimaryAttorneys() || !$this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally()) {
            return "NA";
        }
        return $this->lpaWhenReplacementAttorneyStepInHasValue();
    }

    private function returnToHowReplacementAttorneysMakeDecision()
    {
        if (!$this->lpaHasMultipleReplacementAttorneys()) {
            return "NA";
        }
        if (!(count($this->lpa->document->primaryAttorneys) == 1) &&
            !($this->lpaReplacementAttorneyStepInWhenLastPrimaryUnableAct()) &&
            !($this->lpaPrimaryAttorneysMakeDecisionJointly())) {
            return "NA";
        }
        return $this->lpaHowReplacementAttorneysMakeDecisionHasValue();
    }

    private function returnToCertificateProvider()
    {
        return $this->lpaHasCertificateProvider();
    }

    private function returnToPeopleToNotify()
    {
        return $this->peopleToNotifyHasBeenConfirmed();
    }

    private function returnToInstructions()
    {
        if(!empty($this->lpa->document->instruction) || !empty($this->lpa->document->preference)) {
            return true;
        }
        return "NA";
    }

    private function returnToApplicant()
    {
        return $this->lpaHasApplicant();
    }

    private function returnToCorrespondent()
    {
        return $this->lpaHasCorrespondent();
    }

    private function returnToWhoAreYou()
    {
        return $this->isWhoAreYouAnswered();
    }

    private function returnToRepeatApplication()
    {
        return array_key_exists(Metadata::REPEAT_APPLICATION_CONFIRMED, $this->lpa->metadata);
    }

    private function returnToFeeReduction()
    {
        return $this->lpa->payment instanceof Payment;
    }

    private function returnToCheckout()
    {
        return $this->lpa->payment instanceof Payment &&
            ($this->isEligibleForFeeReduction() || $this->lpa->payment->amount > 0 );
    }

    private function returnToViewDocs()
    {
        return $this->paymentResolved() && $this->lpa->completedAt !== null;
    }

} // class
