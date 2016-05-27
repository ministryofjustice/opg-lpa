<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Application\View\Helper\Traits\ConcatNamesTrait;
use Application\Model\Service\Lpa\Metadata;

abstract class AbstractAccordion extends AbstractHelper
{
    use ConcatNamesTrait;
    
    protected $lpa;
    
    // map route name to method name. Returning value is to be injected into a file under layout/partials/accordion/items
    private $bars = [
        'creation' => [
            'lpa/form-type'                               => 'type',
            'lpa/donor'                                   => 'donor',
            'lpa/when-lpa-starts'                         => "whenLpaStarts",
            'lpa/life-sustaining'                         => "lifeSustaining",
            'lpa/primary-attorney'                        => 'primaryAttorney',
            'lpa/how-primary-attorneys-make-decision'     => 'howPrimaryAttorneysMakeDecision',
            'lpa/replacement-attorney'                    => 'replacementAttorney',
            'lpa/when-replacement-attorney-step-in'       => 'whenReplacementAttorneyStepIn',
            'lpa/how-replacement-attorneys-make-decision' => 'howReplacementAttorneysMakeDecision',
            'lpa/certificate-provider'                    => 'certificateProvider',
            'lpa/people-to-notify'                        => 'peopleToNotify',
            'lpa/instructions'                            => 'instructions',
            'lpa/created'                                 => 'created',
        ],
        'registration' => [
            'lpa/applicant'                     => 'applicant',
            'lpa/correspondent'                 => 'correspondent',
            'lpa/who-are-you'                   => 'whoAreYou',
            'lpa/repeat-application'            => 'repeatApplication',
            'lpa/fee-reduction'                 => 'feeReduction',
            'lpa/payment'                       => 'payment',
            'lpa/payment/return/failure'        => null,
            'lpa/payment/return/cancel'         => null,
        ],
    ];
    
    protected function getBarConfig ($routeName)
    {
        if(array_key_exists($routeName, $this->bars['creation'])) {
            return $this->bars['creation'];
        }
        elseif(array_key_exists($routeName, $this->bars['registration'])) {
            return array_merge(
                $this->bars['creation'],
                $this->bars['registration']
            );
        }
        else {
            return null;
        }
    }

    protected function getRouteName()
    {
        $serviceManager = $this->getView()->getHelperPluginManager()->getServiceLocator();
        $serviceManager instanceof ServiceManager;
    
        $application = $serviceManager->get('application');
        $application instanceof \Zend\Mvc\Application;
    
        return $application->getMvcEvent()->getRouteMatch()->getMatchedRouteName();
    }
    
    protected function type()
    {
        return $this->lpa->document->type;
    }
    
    protected function donor()
    {
        if($this->lpa->document->donor instanceof Donor) {
            return $this->lpa->document->donor->name;
        }
    }
    
    protected function whenLpaStarts()
    {
        if($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            return $this->lpa->document->primaryAttorneyDecisions->when;
        }
    }
    
    protected function lifeSustaining()
    {
        if($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            return $this->lpa->document->primaryAttorneyDecisions->canSustainLife;
        }
    }
    
    protected function primaryAttorney()
    {
        $count = count($this->lpa->document->primaryAttorneys);
        
        if($count > 0) {
            $text = $this->concatNames($this->lpa->document->primaryAttorneys);
        }
        
        return [
            'text' => $text,
            'count' => $count,
        ];
    }
    
    protected function howPrimaryAttorneysMakeDecision()
    {
        if($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            return $this->lpa->document->primaryAttorneyDecisions->how;
        }
    }
    
    protected function replacementAttorney()
    {
        $count = count($this->lpa->document->replacementAttorneys);
        
        if(count($this->lpa->document->replacementAttorneys) == 0) {
            // user has confirmed no replacement attorneys
            if(array_key_exists(Metadata::REPLACEMENT_ATTORNEYS_CONFIRMED, $this->lpa->metadata)) {
                return '';
            }
            else {
                // user has NOT confirmed no replacement attorneys 
                return null;
            }
        }
        
        $ret = [
            'text' => $this->concatNames($this->lpa->document->replacementAttorneys),
            'count' => $count
        ];
        
        return $ret;
    }
    
    protected function whenReplacementAttorneyStepIn()
    {
        if($this->lpa->document->replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
            return $this->lpa->document->replacementAttorneyDecisions->when;
        }
    }
    
    protected function howReplacementAttorneysMakeDecision()
    {
        if($this->lpa->document->replacementAttorneyDecisions instanceof ReplacementAttorneyDecisions) {
            return $this->lpa->document->replacementAttorneyDecisions->how;
        }
    }
    
    protected function certificateProvider()
    {
        if($this->lpa->document->certificateProvider instanceof CertificateProvider) {
            return $this->lpa->document->certificateProvider->name;
        }
    }
    
    protected function peopleToNotify()
    {
        $count = count($this->lpa->document->peopleToNotify);
        
        if($count==0) {
            // user has confirmed no people to notify
            if(array_key_exists(Metadata::PEOPLE_TO_NOTIFY_CONFIRMED, $this->lpa->metadata)) {
                    return '';
            }
            else {
                // user has NOT confirmed no people to notify 
                return null;
            }
        }
        
        $text = $this->concatNames($this->lpa->document->peopleToNotify);
        
        return [
            'text' => $text,
            'count' => $count,
        ];
    }
    
    protected function instructions()
    {
        if(($this->lpa->document->instruction === null)&&($this->lpa->document->preference === null)) return null;
        
        return "Review";
    }
    
    protected function applicant()
    {
        if($this->lpa->document->whoIsRegistering === null) return null;
        
        if($this->lpa->document->whoIsRegistering == 'donor') {
            return ['who' => 'donor', 'name' => (string)$this->lpa->document->donor->name];
        }
        else {
            return ['who'=>'attorney', 'name'=>$this->concatNames($this->lpa->document->primaryAttorneys)];
        }
    }
    
    protected function correspondent()
    {
        if($this->lpa->document->correspondent === null) return null;
        
        return (($this->lpa->document->correspondent->name instanceof Name)?$this->lpa->document->correspondent->name:$this->lpa->document->correspondent->company);
    }
    
    protected function whoAreYou()
    {
        if($this->lpa->whoAreYouAnswered) {
            return "Who was using the LPA tool answered";
        }
        else {
            return null;
        }
    }
    
    protected function repeatApplication()
    {
        if(!array_key_exists(Metadata::REPEAT_APPLICATION_CONFIRMED, $this->lpa->metadata)) return null;
        
        if($this->lpa->repeatCaseNumber === null) {
            return 'This is a new application';
        }
        else {
            return "I’m making a repeat application";
        }
    }
    
    protected function feeReduction()
    {
        if(!($this->lpa->payment instanceof Payment)) return;
        
        if(($this->lpa->payment->reducedFeeReceivesBenefits == null)
                && ($this->lpa->payment->reducedFeeAwardedDamages == null)
                && ($this->lpa->payment->reducedFeeUniversalCredit == null)
                && ($this->lpa->payment->reducedFeeLowIncome == null)) {
            return "I am not applying for reduced fee";
        }
        else {
            return "I am applying for reduced fee";
        }
    }
    
    protected function payment()
    {
        if(($this->lpa->payment instanceof Payment) && ($this->lpa->payment->method !== null)) {
            return 'Application fee: £'.sprintf('%.2f', $this->lpa->payment->amount). ' (Payment method: '.$this->lpa->payment->method.')';
        }
    }
    
    protected function getViewScriptName($barDataFuncName)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $barDataFuncName)).'.twig';
    }
}
