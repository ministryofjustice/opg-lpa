<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;
use Opg\Lpa\DataModel\Lpa\Document\Donor;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\ReplacementAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\CertificateProvider;
use Opg\Lpa\DataModel\Lpa\Elements\Name;

abstract class AbstractAccordion extends AbstractHelper
{
    protected $lpa;
    
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
            return $this->lpa->document->donor->name->__toString();
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
        return ((count($this->lpa->document->primaryAttorneys)==1)? 'is ':'are'). $this->getView()->concatNames($this->lpa->document->primaryAttorneys);
    }
    
    protected function howPrimaryAttorneysMakeDecision()
    {
        if($this->lpa->document->primaryAttorneyDecisions instanceof PrimaryAttorneyDecisions) {
            return $this->lpa->document->primaryAttorneyDecisions->how;
        }
    }
    
    protected function replacementAttorney()
    {
        if(count($this->lpa->document->replacementAttorneys)==0) return '';
        
        return ((count($this->lpa->document->replacementAttorneys)==1)? 'is ':'are ').$this->getView()->concatNames($this->lpa->document->replacementAttorneys);
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
            return $this->lpa->document->certificateProvider->name->__toString();
        }
    }
    
    protected function peopleToNotify()
    {
        if(count($this->lpa->document->peopleToNotify)==0) return '';
        
        return ((count($this->lpa->document->peopleToNotify)==1)? 'is ':'are ').$this->getView()->concatNames($this->lpa->document->peopleToNotify);
    }
    
    protected function instructions()
    {
        return "Review";
    }
    
    protected function applicant()
    {
        if($this->lpa->document->whoIsRegistering == 'donor') {
            return ['who' => 'donor', 'name' => $this->lpa->document->donor->name->__toString()];
        }
        else {
            return ['who'=>'attorney', 'name'=>$this->getView()->concatNames($this->lpa->document->primaryAttorneys)];
        }
    }
    
    protected function correspondent()
    {
        return (($this->lpa->document->correspondent->name instanceof Name)?$this->lpa->document->correspondent->name->__toString():$this->lpa->document->correspondent->company);
    }
    
    protected function whoAreYou()
    {
        if($this->lpa->whoAreYouAnswered) {
            return "Who was using the LPA tool answered";
        }
        else {
            return 'Who was using the LPA tool?';
        }
    }
    
    protected function fee()
    {
        return "Payment";
    }
    
    protected function getViewScriptName($barDataFuncName)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $barDataFuncName)).'.phtml';
    }
}
