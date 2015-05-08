<?php
namespace Application\Model;

use Opg\Lpa\DataModel\Lpa\StateChecker;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Application\Model\Service\Lpa\Metadata;

class FormFlowChecker extends StateChecker
{

    static $accessibleFunctionMap = array(
            'lpa'                                           => 'isLpaAccessible',
            'lpa/form-type'                                 => 'isFormTypeAccessible',
            'lpa/donor'                                     => 'isDonorAccessible',
            'lpa/donor/add'                                 => 'isDonorAddAccessible',
            'lpa/donor/edit'                                => 'isDonorEditAccessible',
            'lpa/when-lpa-starts'                           => 'isWhenLpaStartsAccessible',
            'lpa/life-sustaining'                           => 'isLifeSustainingAccessible',
            'lpa/primary-attorney'                          => 'isAttorneyAccessible',
            'lpa/primary-attorney/add'                      => 'isAttorneyAddAccessible',
            'lpa/primary-attorney/edit'                     => 'isAttorneyEditAccessible',
            'lpa/primary-attorney/delete'                   => 'isAttorneyDeleteAccessible',
            'lpa/primary-attorney/add-trust'                => 'isAttorneyAddTrustAccessible',
            'lpa/primary-attorney/edit-trust'               => 'isAttorneyEditTrustAccessible',
            'lpa/primary-attorney/delete-trust'             => 'isAttorneyDeleteTrustAccessible',
            'lpa/how-primary-attorneys-make-decision'       => 'isHowPrimaryAttorneysMakeDecisionAccessible',
            'lpa/replacement-attorney'                      => 'isReplacementAttorneyAccessible',
            'lpa/replacement-attorney/add'                  => 'isReplacementAttorneyAddAccessible',
            'lpa/replacement-attorney/edit'                 => 'isReplacementAttorneyEditAccessible',
            'lpa/replacement-attorney/delete'               => 'isReplacementAttorneyDeleteAccessible',
            'lpa/replacement-attorney/add-trust'            => 'isReplacementAttorneyAddTrustAccessible',
            'lpa/replacement-attorney/edit-trust'           => 'isReplacementAttorneyEditTrustAccessible',
            'lpa/replacement-attorney/delete-trust'         => 'isReplacementAttorneyDeleteTrustAccessible',
            'lpa/when-replacement-attorney-step-in'         => 'isWhenReplacementAttorneyStepInAccessible',
            'lpa/how-replacement-attorneys-make-decision'   => 'isHowReplacementAttorneysMakeDecisionAccessible',
            'lpa/certificate-provider'                      => 'isCertificateProviderAccessible',
            'lpa/certificate-provider/add'                  => 'isCertificateProviderAddAccessible',
            'lpa/certificate-provider/edit'                 => 'isCertificateProviderEditAccessible',
            'lpa/people-to-notify'                          => 'isPeopleToNotifyAccessible',
            'lpa/people-to-notify/add'                      => 'isPeopleToNotifyAddAccessible',
            'lpa/people-to-notify/edit'                     => 'isPeopleToNotifyEditAccessible',
            'lpa/people-to-notify/delete'                   => 'isPeopleToNotifyDeleteAccessible',
            'lpa/instructions'                              => 'isInstructionsAccessible',
            'lpa/created'                                   => 'isCreatedAccessible',
            'lpa/register'                                  => 'isCreatedAccessible',
            'lpa/download'                                  => 'isDownloadAccessible',
            'lpa/applicant'                                 => 'isApplicantAccessible',
            'lpa/correspondent'                             => 'isCorrespondentAccessible',
            'lpa/correspondent/edit'                        => 'isCorrespondentEditAccessible',
            'lpa/who-are-you'                               => 'isWhoAreYouAccessible',
            'lpa/repeat-application'                        => 'isRepeatApplicationAccessible',
            'lpa/fee-reduction'                             => 'isFeeReductionAccessible',
            'lpa/benefits'                                  => 'isBenefitsAccessible',
            'lpa/income-and-universal-credit'               => 'isIncomeAndUniversalCreditAccessible',
            'lpa/payment'                                   => 'isPaymentAccessible',
            'lpa/payment/return/success'                    => 'isOnlinePaymentSuccessAccessible',
            'lpa/payment/return/failure'                    => 'isOnlinePaymentFailureAccessible',
            'lpa/payment/return/cancel'                     => 'isOnlinePaymentCancelAccessible',
            'lpa/payment/return/pending'                    => 'isOnlinePaymentPendingAccessible',
            'lpa/complete'                                  => 'isCompleteAccessible',
            'lpa/date-check'                                => 'isViewDocsAccessible',
            'lpa/view-docs'                                 => 'isViewDocsAccessible',
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
            'lpa/created'                                   => 'returnToCreateLpa',
            'lpa/applicant'                                 => 'returnToApplicant',
            'lpa/correspondent'                             => 'returnToCorrespondent',
            'lpa/who-are-you'                               => 'returnToWhoAreYou',
            'lpa/repeat-application'                        => 'returnToRepeatApplication',
            'lpa/fee-reduction'                             => 'returnToFeeReduction',
            'lpa/benefits'                                  => 'returnToBenefits',
            'lpa/income-and-universal-credit'               => 'returnToIncomeAndUniversalCredit',
            'lpa/payment'                                   => 'returnToPayment',
            'lpa/view-docs'                                 => 'returnToViewDocs',
    );
    
    static $nextRouteMap = array(
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
            'lpa/primary-attorney/edit-trust'               => 'lpa/primary-attorney',
            'lpa/primary-attorney/delete-trust'             => 'lpa/primary-attorney',
            'lpa/how-primary-attorneys-make-decision'       => 'lpa/replacement-attorney',
            'lpa/replacement-attorney'                      => ['lpa/when-replacement-attorney-step-in', 'lpa/how-replacement-attorneys-make-decision', 'lpa/how-replacement-attorneys-make-decision', 'lpa/certificate-provider'],
            'lpa/replacement-attorney/add'                  => 'lpa/replacement-attorney',
            'lpa/replacement-attorney/edit'                 => 'lpa/replacement-attorney',
            'lpa/replacement-attorney/delete'               => 'lpa/replacement-attorney',
            'lpa/replacement-attorney/add-trust'            => 'lpa/replacement-attorney',
            'lpa/replacement-attorney/edit-trust'           => 'lpa/replacement-attorney',
            'lpa/replacement-attorney/delete-trust'         => 'lpa/replacement-attorney',
            'lpa/when-replacement-attorney-step-in'         => ['lpa/how-replacement-attorneys-make-decision','lpa/certificate-provider'],
            'lpa/how-replacement-attorneys-make-decision'   => 'lpa/certificate-provider',
            'lpa/certificate-provider'                      => 'lpa/people-to-notify',
            'lpa/certificate-provider/add'                  => 'lpa/certificate-provider',
            'lpa/certificate-provider/edit'                 => 'lpa/certificate-provider',
            'lpa/people-to-notify'                          => 'lpa/instructions',
            'lpa/people-to-notify/add'                      => 'lpa/people-to-notify',
            'lpa/people-to-notify/edit'                     => 'lpa/people-to-notify',
            'lpa/people-to-notify/delete'                   => 'lpa/people-to-notify',
            'lpa/instructions'                              => 'lpa/created',
            'lpa/applicant'                                 => 'lpa/correspondent',
            'lpa/correspondent'                             => 'lpa/who-are-you',
            'lpa/correspondent/edit'                        => 'lpa/correspondent',
            'lpa/who-are-you'                               => 'lpa/repeat-application',
            'lpa/repeat-application'                        => 'lpa/fee-reduction',
            'lpa/fee-reduction'                             => ['lpa/benefits', 'lpa/payment'],
            'lpa/benefits'                                  => ['lpa/income-and-universal-credit', 'lpa/complete'],
            'lpa/income-and-universal-credit'               => ['lpa/payment', 'lpa/complete'],
            'lpa/payment'                                   => 'lpa/complete',
            
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
        // check if route exists in the mapping table.
        if(!array_key_exists($currentRouteName, static::$accessibleFunctionMap)) {
            throw new \RuntimeException('Check() received an undefined route: '. $currentRouteName);
        }
        
        // once payment date has been set, user will not be able to view any page other than lpa/view-docs and lpa/complete.
        if(($this->lpa->payment instanceof Payment)  && ($this->lpa->payment->date instanceof \DateTime)) {
            if(($currentRouteName != 'lpa/complete') && 
               ($currentRouteName != 'lpa/date-check') && 
                ($currentRouteName != 'lpa/download')
            ) {
                return 'lpa/view-docs';
            }
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
    
    public function backToForm($requestRoute = 'lpa/view-docs')
    {
        $checkFunction = static::$returnFunctionMap[$requestRoute];
        $calculatedRoute = call_user_func(array($this, $checkFunction));
        if($calculatedRoute == $requestRoute) {
            return $requestRoute;
        }
        else {
            return $this->backToForm($calculatedRoute);
        }
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
        return $this->isAttorneyAccessible();
    }
    
    private function isAttorneyEditAccessible($idx)
    {
        if($this->lpaHasPrimaryAttorney($idx)) {
            return true;
        }
        else {
            return 'lpa/primary-attorney';
        }
    }
    
    private function isAttorneyDeleteAccessible($idx)
    {
        if($this->lpaHasPrimaryAttorney($idx)) {
            return true;
        }
        else {
            return 'lpa/primary-attorney';
        }
    }

    private function isAttorneyAddTrustAccessible()
    {
        if(($this->isAttorneyAccessible()===true) && (!$this->lpaHasTrustCorporation('primary'))) {
            return true;
        }
        else {
            return 'lpa/primary-attorney';
        }
    }
    
    private function isAttorneyEditTrustAccessible()
    {
        if($this->lpaHasTrustCorporation('primary')) {
            return true;
        }
        else {
            return 'lpa/primary-attorney';
        }
    }
    
    private function isAttorneyDeleteTrustAccessible($idx)
    {
        if($this->lpaHasTrustCorporation('primary')) {
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
            if(($this->lpa->document->primaryAttorneyDecisions instanceof AbstractDecisions) 
                &&$this->lpaHowPrimaryAttorneysMakeDecisionHasValue()) {
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
        return $this->isReplacementAttorneyAccessible();
    }

    private function isReplacementAttorneyEditAccessible($idx)
    {
        if($this->lpaHasReplacementAttorney($idx)) {
            return true;
        }
        else {
            return 'lpa/replacement-attorney';
        }
    }
    
    private function isReplacementAttorneyDeleteAccessible($idx)
    {
        if($this->lpaHasReplacementAttorney($idx)) {
            return true;
        }
        else {
            return 'lpa/replacement-attorney';
        }
    }

    private function isReplacementAttorneyAddTrustAccessible()
    {
        if(($this->isReplacementAttorneyAccessible()===true) && (!$this->lpaHasTrustCorporation('replacement'))) {
            return true;
        }
        else {
            return 'lpa/replacement-attorney';
        }
    }
    
    private function isReplacementAttorneyEditTrustAccessible()
    {
        if($this->lpaHasTrustCorporation('replacement')) {
            return true;
        }
        else {
            return 'lpa/replacement-attorney';
        }
    }
    
    private function isReplacementAttorneyDeleteTrustAccessible($idx)
    {
        if($this->lpaHasTrustCorporation('replacement')) {
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
            ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionDepends())
            || ((count($this->lpa->document->replacementAttorneys) == 0) && array_key_exists(Metadata::REPLACEMENT_ATTORNEYS_CONFIRMED, $this->lpa->metadata))
            || ((count($this->lpa->document->replacementAttorneys) == 1) && (count($this->lpa->document->primaryAttorneys) == 1))
            || ((count($this->lpa->document->replacementAttorneys) == 1) && $this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointly())
            || ($this->lpaHasMultipleReplacementAttorneys() && (count($this->lpa->document->primaryAttorneys) == 1) && $this->lpaHowReplacementAttorneysMakeDecisionHasValue())
            || ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaHasMultipleReplacementAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointly() && $this->lpaHowReplacementAttorneysMakeDecisionHasValue())
            || ((count($this->lpa->document->replacementAttorneys) == 1) && $this->lpaHasMultiplePrimaryAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally() && $this->lpaWhenReplacementAttorneyStepInHasValue())
            || ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaHasMultipleReplacementAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally() && ($this->lpa->document->replacementAttorneyDecisions instanceof AbstractDecisions) && in_array($this->lpa->document->replacementAttorneyDecisions->when, [ReplacementAttorneyDecisions::LPA_DECISION_WHEN_FIRST, ReplacementAttorneyDecisions::LPA_DECISION_WHEN_DEPENDS]))
            || ($this->lpaHasMultiplePrimaryAttorneys() && $this->lpaHasMultipleReplacementAttorneys() && $this->lpaPrimaryAttorneysMakeDecisionJointlyAndSeverally() && $this->lpaReplacementAttorneyStepInWhenLastPrimaryUnableAct() && $this->lpaHowReplacementAttorneysMakeDecisionHasValue())
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
        return $this->isCertificateProviderAccessible();
    }
    
    private function isCertificateProviderEditAccessible()
    {
        if($this->lpaHasCertificateProvider()) {
            return true;
        }
        else {
            return 'lpa/certificate-provider';
        }
    }
    
    private function isPeopleToNotifyAccessible()
    {
        if($this->lpaHasCertificateProvider()) {
            return true;
        }
        else {
            return 'lpa/certificate-provider';
        }
    }

    private function isPeopleToNotifyAddAccessible()
    {
        return $this->isPeopleToNotifyAccessible();
    }

    private function isPeopleToNotifyEditAccessible($idx)
    {
        if($this->lpaHasPeopleToNotify($idx)) {
            return true;
        }
        else {
            return 'lpa/people-to-notify';
        }
    }

    private function isPeopleToNotifyDeleteAccessible($idx)
    {
        if($this->lpaHasPeopleToNotify($idx)) {
            return true;
        }
        else {
            return 'lpa/people-to-notify';
        }
    }
    
    private function isInstructionsAccessible()
    {
        if($this->lpaHasCertificateProvider() && $this->peopleToNotifyHasBeenConfirmed()) {
            return true;
        }
        else {
            return 'lpa/people-to-notify';
        }
    }
    
    private function isCreatedAccessible()
    {
        if($this->lpaHasFinishedCreation()) {
            return true;
        }
        else {
            return 'lpa/instructions';
        }
    }
    
    private function isDownloadAccessible($pdfType)
    {
        if(!in_array($pdfType, ['lp1', 'lp3', 'lpa120'])) {
            return false;
        }
        
        if($pdfType == 'lp1') {
            if($this->isCreatedAccessible() === true) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            if($this->isCompleteAccessible() === true) {
                return true;
            }
            else {
                return false;
            }
        }
    }
    
    private function isApplicantAccessible()
    {
        if($this->lpaHasCreated()) {
            return true;
        }
        else {
            return 'lpa/created';
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
        if(array_key_exists(Metadata::REPEAT_APPLICATION_CONFIRMED, $this->lpa->metadata)) {
            return true;
        }
        else {
            return 'lpa/repeat-application';
        }
    }

    private function isBenefitsAccessible()
    {
        if(array_key_exists(Metadata::APPLY_FOR_FEE_REDUCTION, $this->lpa->metadata) && 
                ($this->lpa->metadata[Metadata::APPLY_FOR_FEE_REDUCTION] === true)) {
            return true;
        }
        else {
            return 'lpa/fee-reduction';
        }
    }

    private function isIncomeAndUniversalCreditAccessible()
    {
        if(array_key_exists(Metadata::APPLY_FOR_FEE_REDUCTION, $this->lpa->metadata) && 
                ($this->lpa->metadata[Metadata::APPLY_FOR_FEE_REDUCTION] === true) &&
                ($this->lpa->payment instanceof Payment) && 
                ($this->lpa->payment->reducedFeeReceivesBenefits !== null) && 
                ($this->lpa->payment->reducedFeeAwardedDamages !== true)) {
            return true;
        }
        else {
            return 'lpa/benefits';
        }
    }
    
    private function isPaymentAccessible()
    {
        if(array_key_exists(Metadata::APPLY_FOR_FEE_REDUCTION, $this->lpa->metadata)) {
            if($this->lpa->metadata[Metadata::APPLY_FOR_FEE_REDUCTION] === false) {
                return true;
            }
            else {
                if($this->lpa->payment instanceof Payment) {
                    if(($this->lpa->payment->reducedFeeUniversalCredit === false) && ($this->lpa->payment->reducedFeeLowIncome !== null)) {
                        return true;
                    }
                    else {
                        return 'lpa/income-and-universal-credit';
                    }
                }
                else {
                    return 'lpa/benefits';
                }
            }
        }
        else {
            return 'lpa/fee-reduction';
        }
    }
    
    private function isOnlinePaymentSuccessAccessible()
    {
        if(($this->lpa->payment instanceof Payment) && 
                ($this->lpa->payment->method == 'card')) {
            return true;
        }
        else {
            return 'lpa/payment';
        }
    }
    
    private function isOnlinePaymentFailureAccessible()
    {
        if(($this->lpa->payment instanceof Payment) && 
                ($this->lpa->payment->method == 'card')) {
            return true;
        }
        else {
            return 'lpa/payment';
        }
    }
    
    private function isOnlinePaymentCancelAccessible()
    {
        if(($this->lpa->payment instanceof Payment) && 
                ($this->lpa->payment->method == 'card')) {
            return true;
        }
        else {
            return 'lpa/payment';
        }
    }
    
    private function isOnlinePaymentPendingAccessible()
    {
        if(($this->lpa->payment instanceof Payment) && 
                ($this->lpa->payment->method == 'card')) {
            return true;
        }
        else {
            return 'lpa/payment';
        }
    }
    
    private function isCompleteAccessible()
    {
        if($this->paymentResolved()) {
            return true;
        }
        else {
            return 'lpa/payment';
        }
    }
    
    private function isViewDocsAccessible()
    {
        if($this->paymentResolved() && ($this->lpa->completedAt !== null)) {
            return true;
        }
        else {
            return 'lpa/payment';
        }
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
    
######################## return functions #####################    
    
    private function returnToFormType()
    {
        if($this->lpaHasType()) {
            return 'lpa/form-type';
        }
        else {
            return 'lpa/form-type';
        }
    }
    
    private function returnToDonor()
    {
        if($this->lpaHasDonor()) {
            return 'lpa/donor';
        }
        else {
            return 'lpa/form-type';
        }
    }
    
    
    private function returnToLifeSustaining()
    {
        if($this->lpaHasLifeSustaining()) {
            return 'lpa/life-sustaining';
        }
        else {
            return 'lpa/donor';
        }
    }
    
    private function returnToWhenLpaStarts()
    {
        if($this->lpaHasWhenLpaStarts()) {
            return 'lpa/when-lpa-starts';
        }
        else {
            return 'lpa/donor';
        }
    }
    
    private function returnToPrimaryAttorney()
    {
        if($this->lpaHasPrimaryAttorney()) {
            return 'lpa/primary-attorney';
        }
        else {
            if($this->lpa->document->type == Document::LPA_TYPE_HW) {
                return 'lpa/life-sustaining';
            }
            else {
                return 'lpa/when-lpa-starts';
            }
        }
    }
    
    private function returnToHowPrimaryAttorneysMakeDecision()
    {
        if($this->lpaHowPrimaryAttorneysMakeDecisionHasValue()) {
            return 'lpa/how-primary-attorneys-make-decision';
        }
        else {
            return 'lpa/primary-attorney';
        }
    }
    
    private function returnToReplacementAttorney()
    {
        if($this->replacementAttorneyHasBeenConfirmed()) {
            return 'lpa/replacement-attorney';
        }
        else {
            if($this->lpaHasMultiplePrimaryAttorneys()) {
                if($this->lpaHowPrimaryAttorneysMakeDecisionHasValue()) {
                    return 'lpa/how-primary-attorneys-make-decision';
                }
                else {
                    return 'lpa/primary-attorney';
                }
            }
            else {
                return 'lpa/primary-attorney';
            }
        }
    }
    
    private function returnToWhenReplacementAttorneyStepIn()
    {
        if($this->lpaWhenReplacementAttorneyStepInHasValue()) {
            return 'lpa/when-replacement-attorney-step-in';
        }
        else {
            return 'lpa/replacement-attorney';
        }
    }
    
    private function returnToHowReplacementAttorneysMakeDecision()
    {
        if($this->lpaHowReplacementAttorneysMakeDecisionHasValue()) {
            return 'lpa/how-replacement-attorneys-make-decision';
        }
        else {
            if($this->lpaWhenReplacementAttorneyStepInHasValue()) {
                return 'lpa/when-replacement-attorney-ste-in';
            }
            else {
                return 'lpa/replacement-attorney';
            }
        }
    }
    
    private function returnToCertificateProvider()
    {
        if($this->lpaHasCertificateProvider()) {
            return 'lpa/certificate-provider';
        }
        else {
            if($this->lpaHowReplacementAttorneysMakeDecisionHasValue()) {
                return 'lpa/how-replacement-attorneys-make-decision';
            }
            elseif($this->lpaWhenReplacementAttorneyStepInHasValue()) {
                return 'lpa/when-replacement-attorney-step-in';
            }
            else {
                return 'lpa/replacement-attorney';
            }
        }
    }
    
    private function returnToPeopleToNotify()
    {
        if($this->peopleToNotifyHasBeenConfirmed()) {
            return 'lpa/people-to-notify';
        }
        else {
            return 'lpa/certificate-provider';
        }
    }
    
    private function returnToInstructions()
    {
        if(($this->lpa->document->instruction !== null) || ($this->lpa->document->preference !== null)) {
            return 'lpa/instructions';
        }
        else {
            return 'lpa/people-to-notify';
        }
    }
    
    private function returnToCreateLpa()
    {
        if($this->lpa->createdAt !== null) {
            return 'lpa/created';
        }
        else {
            return 'lpa/instructions';
        }
    }
    
    private function returnToApplicant()
    {
        if($this->lpaHasApplicant()) {
            return 'lpa/applicant';
        }
        else {
            return 'lpa/created';
        }
    }
    
    private function returnToCorrespondent()
    {
        if($this->lpaHasCorrespondent()) {
            return 'lpa/correspondent';
        }
        else {
            return 'lpa/applicant';
        }
    }
    
    private function returnToWhoAreYou()
    {
        if($this->isWhoAreYouAnswered()) {
            return 'lpa/who-are-you';
        }
        else {
            return 'lpa/correspondent';
        }
    }
    
    private function returnToRepeatApplication()
    {
        if($this->isWhoAreYouAnswered() && array_key_exists(Metadata::REPEAT_APPLICATION_CONFIRMED, $this->lpa->metadata)) {
            return 'lpa/repeat-application';
        }
        else {
            return 'lpa/who-are-you';
        }
    }
    
    private function returnToFeeReduction()
    {
        if($this->hasFeeDetermined()) {
            return 'lpa/fee-reduction';
        }
        else {
            return 'lpa/repeat-application';
        }
    }
    
    private function returnToBenefits()
    {
        if($this->hasFeeDetermined()) {
            return 'lpa/benefits';
        }
        else {
            return 'lpa/fee-reduction';
        }
    }
    
    private function returnToIncomeAndUniversalCredit()
    {
        if($this->hasFeeDetermined()) {
            return 'lpa/income-and-universal-credit';
        }
        else {
            return 'lpa/benefits';
        }
    }
    
    private function returnToPayment()
    {
        if($this->hasFeeDetermined()) {
            return 'lpa/payment';
        }
        else {
            return 'lpa/income-and-universal-credit';
        }
    }
    
    private function returnToViewDocs()
    {
        if($this->paymentResolved() && ($this->lpa->completedAt !== null)) {
            return 'lpa/view-docs';
        }
        else {
            return 'lpa/payment';
        }
    }

} // class
