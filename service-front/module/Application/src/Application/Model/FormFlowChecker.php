<?php
namespace Application\Model;

use Opg\Lpa\DataModel\Lpa\StateChecker;

use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\AbstractDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

class FormFlowChecker extends StateChecker
{

    static $checkerFunctionMap = array(
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
            'lpa/fee'                                       => 'isFeeAccessible',
            'lpa/payment'                                   => 'isPaymentAccessible',
            'lpa/payment/return/success'                    => 'isOnlinePaymentSuccessAccessible',
            'lpa/payment/return/failure'                    => 'isOnlinePaymentFailureAccessible',
            'lpa/payment/return/cancel'                     => 'isOnlinePaymentCancelAccessible',
            'lpa/payment/return/pending'                    => 'isOnlinePaymentPendingAccessible',
            'lpa/complete'                                  => 'isCompleteAccessible',
            'lpa/view-docs'                                 => 'isViewDocsAccessible',
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
            'lpa/who-are-you'                               => 'lpa/fee',
            'lpa/fee'                                       => 'lpa/complete',
    );

    public function check($currentRouteName, $param=null)
    {
        // check if route exists
        if(!array_key_exists($currentRouteName, static::$checkerFunctionMap)) {
            throw new \RuntimeException('Check() received an undefined route: '. $currentRouteName);
        }
        
        // once payment date has been set, user will not be able to view any page other than lpa/view-docs and lpa/complete.
        if(($this->lpa->payment instanceof Payment)  && ($this->lpa->payment->date instanceof \DateTime)) {
            if(($currentRouteName != 'lpa/complete') && ($currentRouteName != 'lpa/download')) {
                return 'lpa/view-docs';
            }
        }
        
        $checkFunction = static::$checkerFunctionMap[$currentRouteName];
        $checkValue = call_user_func(array($this, $checkFunction), $param);
        if($checkValue === true) {
            return $currentRouteName;
        }
        else {
            if(array_key_exists($checkValue, static::$checkerFunctionMap)) {
                return $this->check($checkValue);
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
                    if($this->check($nextRoute, $personIdex) == $nextRoute) {
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
        if(($this->isAttorneyAccessible()==true) && (!$this->lpaHasTrustCorporation('primary'))) {
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
            || (count($this->lpa->document->replacementAttorneys) == 0)
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
        if($this->lpaHasCertificateProvider()) {
            return true;
        }
        else {
            return 'lpa/people-to-notify';
        }
    }
    
    private function isCreatedAccessible()
    {
        if($this->lpaHasCertificateProvider() && ($this->lpa->document->instruction !== null)) {
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
        if($this->lpaHasCorrespondent()) {
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
        
    private function isFeeAccessible()
    {
        if($this->isWhoAreYouAnswered()) {
            return true;
        }
        else {
            return 'lpa/who-are-you';
        }
    }

    private function isPaymentAccessible()
    {
        if($this->hasFeeCompleted()) {
            return true;
        }
        else {
            return 'lpa/who-are-you';
        }
    }
    
    private function isOnlinePaymentSuccessAccessible()
    {
        if($this->isPaymentAccessible() === true) {
            return true;
        }
        else {
            return 'lpa/fee';
        }
    }
    
    private function isOnlinePaymentFailureAccessible()
    {
        if($this->isPaymentAccessible() === true) {
            return true;
        }
        else {
            return 'lpa/fee';
        }
    }
    
    private function isOnlinePaymentCancelAccessible()
    {
        if($this->isPaymentAccessible() === true) {
            return true;
        }
        else {
            return 'lpa/fee';
        }
    }
    
    private function isOnlinePaymentPendingAccessible()
    {
        if($this->isPaymentAccessible() === true) {
            return true;
        }
        else {
            return 'lpa/fee';
        }
    }
    
    private function isCompleteAccessible()
    {
        if($this->paymentResolved()) {
            return true;
        }
        else {
            return 'lpa/fee';
        }
    }
    
    private function isViewDocsAccessible()
    {
        return $this->isCompleteAccessible();
    }

} // class
