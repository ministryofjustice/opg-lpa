<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Elements\Name;
use Application\View\Helper\Traits\ConcatNames;

abstract class AbstractAccordion extends AbstractHelper
{
    use ConcatNames;
    
    protected $lpa;
    
    // map route name to method name
    private $bars = [
        'creation' => [
            'lpa/form-type' => 'type',
            'lpa/donor' => 'donor',
            'lpa/when-lpa-starts' => "whenLpaStarts",
            'lpa/life-sustaining' => "lifeSustaining",
            'lpa/primary-attorney' => 'primaryAttorney',
            'lpa/how-primary-attorneys-make-decision' => 'howPrimaryAttorneysMakeDecision',
            'lpa/replacement-attorney' => 'replacementAttorney',
            'lpa/when-replacement-attorney-step-in' => 'whenReplacementAttorneyStepIn',
            'lpa/how-replacement-attorneys-make-decision' => 'howReplacementAttorneysMakeDecision',
            'lpa/certificate-provider' => 'certificateProvider',
            'lpa/people-to-notify' => 'peopleToNotify',
            'lpa/instructions' => 'instructions',
        ],
        'registration' => [
            'lpa/applicant' => 'applicant',
            'lpa/correspondent' => 'correspondent',
            'lpa/who-are-you' => 'whoAreYou',
            'lpa/fee' => 'fee',
            'lpa/payment/return/failure' => null,
            'lpa/payment/return/cancel' => null,
        ],
    ];
    
    protected function getBarConfig ($routeName)
    {
        if(array_key_exists($routeName, $this->bars['creation'])) {
            return $this->bars['creation'];
        }
        elseif(array_key_exists($routeName, $this->bars['registration'])) {
            return $this->bars['registration'];
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
        if(count($this->lpa->document->primaryAttorneys) > 0) {
            return ((count($this->lpa->document->primaryAttorneys)==1)? 'is ':'are').' '.$this->concatNames($this->lpa->document->primaryAttorneys);
        }
    }
    
    protected function howPrimaryAttorneysMakeDecision()
    {
        if($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            return $this->lpa->document->primaryAttorneyDecisions->how;
        }
    }
    
    protected function replacementAttorney()
    {
        if(count($this->lpa->document->replacementAttorneys) == 0) {
            // user has confirmed no replacement attorneys
            if(is_array($this->lpa->metadata) && array_key_exists('lpa-has-no-replacement-attorneys', $this->lpa->metadata) && $this->lpa->metadata['lpa-has-no-replacement-attorneys']) {
                return '';
            }
            else {
                // user has NOT confirmed no replacement attorneys 
                return null;
            }
        }
        
        return ((count($this->lpa->document->replacementAttorneys)==1)? 'is ':'are ').' '.$this->concatNames($this->lpa->document->replacementAttorneys);
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
        if(count($this->lpa->document->peopleToNotify)==0) {
            // user has confirmed no people to notify
            if(is_array($this->lpa->metadata) && array_key_exists('lpa-has-no-people-to-notify', $this->lpa->metadata) && $this->lpa->metadata['lpa-has-no-people-to-notify']) {
                return '';
            }
            else {
                // user has NOT confirmed no people to notify 
                return null;
            }
        }
        
        return ((count($this->lpa->document->peopleToNotify)==1)? 'is ':'are ').' '.$this->concatNames($this->lpa->document->peopleToNotify);
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
    
    protected function fee()
    {
        if($this->lpa->payment === null) return null;
        
        return "Payment";
    }
    
    protected function getViewScriptName($barDataFuncName)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $barDataFuncName)).'.phtml';
    }
}
