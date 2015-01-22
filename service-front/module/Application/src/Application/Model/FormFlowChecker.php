<?php
namespace Application\Model;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\AbstractAttorney;
use Symfony\Component\Intl\Exception\UnexpectedTypeException;

class FormFlowChecker
{
    private $lpa;
    
    static $checkerFunctionMap = array(
            'lpa/applicant'                                 => 'isApplicantAccessible',
            'lpa/certificate-provider'                      => 'isCertificateProviderAccessible',
            'lpa/certificate-provider/add'                  => 'isCertificateProviderAddAccessible',
            'lpa/certificate-provider/edit'                 => 'isCertificateProviderEditAccessible',
            'lpa/complete'                                  => 'isCompleteAccessible',
            'lpa/correspondant'                             => 'isCorrespondentAccessible',
            'lpa/correspondant/edit'                        => 'isCorrespondentEditAccessible',
            'lpa/created'                                   => 'isCreatedAccessible',
            'lpa/donor'                                     => 'isDonorAccessible',
            'lpa/donor/add'                                 => 'isDonorAddAccessible',
            'lpa/donor/edit'                                => 'isDonorEditAccessible',
            'lpa/download'                                  => 'isDownloadAccessible',
            'lpa/fee'                                       => 'isFeeAccessible',
            'lpa/how-primary-attorneys-make-decision'       => 'isHowPrimaryAttorneysMakeDecisionAccessible',
            'lpa/how-replacement-attorneys-make-decision'   => 'isHowReplacementAttorneysMakeDecisionAccessible',
            'lpa/instructions'                              => 'isInstructionsAccessible',
            'lpa/life-sustaining'                           => 'isLifeSustainingAccessible',
            'lpa/online-payment-success'                    => 'isOnlinePaymentSuccessAccessible',
            'lpa/online-payment-unsuccessful'               => 'isOnlinePaymentUnsuccessfulAccessible',
            'lpa/people-to-notify'                          => 'isPeopleToNotifyAccessible',
            'lpa/people-to-notify/add'                      => 'isPeopleToNotifyAddAccessible',
            'lpa/people-to-notify/edit'                     => 'isPeopleToNotifyEditAccessible',
            'lpa/people-to-notify/delete'                   => 'isPeopleToNotifyDeleteAccessible',
            'lpa/primary-attorney'                          => 'isAttorneyAccessible',
            'lpa/primary-attorney/add'                      => 'isAttorneyAddAccessible',
            'lpa/primary-attorney/edit'                     => 'isAttorneyEditAccessible',
            'lpa/primary-attorney/delete'                   => 'isAttorneyEditAccessible',
            'lpa/replacement-attorney'                      => 'isReplacementAttorneyAccessible',
            'lpa/replacement-attorney/add'                  => 'isReplacementAttorneyAddAccessible',
            'lpa/replacement-attorney/edit'                 => 'isReplacementAttorneyEditAccessible',
            'lpa/replacement-attorney/delete'               => 'isReplacementAttorneyDeleteAccessible',
            'lpa/form-type'                                 => 'isFormTypeAccessible',
            'lpa/what-is-my-role'                           => 'isWhatIsMyRoleAccessible',
            'lpa/when-lpa-starts'                           => 'isWhenLpaStartsAccessible',
            'lpa/when-replacement-attorney-step-in'         => 'isWhenReplacementAttorneyStepInAccessible',
    );
    
    public function __construct(Lpa $lpa)
    {
        $this->setLpa($lpa);
    }
    
    public function setLpa(Lpa $lpa)
    {
        $this->lpa = $lpa;
    }
    
    public function check($currentRouteName, $personIdex=null)
    {
        if(!array_key_exists($currentRouteName, static::$checkerFunctionMap)) {
            throw new \RuntimeException('Check() received an undefined route: '. $currentRouteName);
        }
        
        $checkFunction = static::$checkerFunctionMap[$currentRouteName];
        $checkValue = call_user_func(array($this, $checkFunction), $personIdex);
        
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
    
    
###################  Private methods - accessible methods #################################################
    
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
            if(in_array($this->lpa->document->primaryAttorneyDecisions->how, array(
                    PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY_AND_SEVERALLY,
                    PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY,
                    PrimaryAttorneyDecisions::LPA_DECISION_HOW_DEPENDS))) {
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
    
    
###################  Private methods - lpa property value check methods #################################################

    private function lpaHasMultipleReplacementAttorneys()
    {
        return ($this->lpaHasReplacementAttorney() && (count($this->lpa->document->replacementAttorneys) > 1));
    }
    
    private function lpaHasReplacementAttorney($index = null)
    {
        if($index === null) {
            return ($this->lpaHasPrimaryAttorney() 
                && ( count( $this->lpa->document->replacementAttorneys ) > 0 ) );
        }
        else {
            return ($this->lpaHasPrimaryAttorney()
                && array_key_exists($index, $this->lpa->document->replacementAttorneys)
                && ($this->lpa->document->replacementAttorneys[$index] instanceof AbstractAttorney));
        }
    }
    
    private function lpaHasMultiplePrimaryAttorneys()
    {
        return ($this->lpaHasPrimaryAttorney() && (count($this->lpa->document->primaryAttorneys) > 1));
    }
    
    private function lpaHasPrimaryAttorney($index = null)
    {
        if($index === null) {
            return (($this->lpaHasWhenLpaStarts() || $this->lpaHasLifeSustaining())
                && ( count( $this->lpa->document->primaryAttorneys ) > 0 ) );
        }
        else {
            return (($this->lpaHasWhenLpaStarts() || $this->lpaHasLifeSustaining())
                && array_key_exists($index, $this->lpa->document->primaryAttorneys)
                && ($this->lpa->document->primaryAttorneys[$index] instanceof AbstractAttorney));
        }
    }
    
    private function lpaHasLifeSustaining()
    {
        return ($this->lpaHasDonor()
            && ($this->lpa->document->type == Document::LPA_TYPE_HW) 
            && ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) 
            && is_bool($this->lpa->document->primaryAttorneyDecisions->canSustainLife));
    }

    private function lpaHasWhenLpaStarts()
    {
        return ($this->lpaHasDonor()
            && ($this->lpa->document->type == Document::LPA_TYPE_PF)
            && ($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions)
            && (in_array($this->lpa->document->primaryAttorneyDecisions->when, array(PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NO_CAPACITY, PrimaryAttorneyDecisions::LPA_DECISION_WHEN_NOW))));
    }
    
    private function lpaHasDonor()
    {
        return ($this->lpaHasType() && ($this->lpa->document->donor instanceof Donor));
    }
    
    private function lpaHasType()
    {
        return $this->lpaHasDocument() && ($this->lpa->document->type != null);
    }
    
    private function lpaHasDocument()
    {
        return $this->lpa->document instanceof Document;
    }
}
