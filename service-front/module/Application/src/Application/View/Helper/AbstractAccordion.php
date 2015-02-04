<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;

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
            'lpa/what-is-my-role' => 'whatIsMyRole',
            'lpa/fee' => 'fee',
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
        return $this->lpa->document->donor->name;
    }
    
    protected function whenLpaStarts()
    {
        return $this->lpa->document->primaryAttorneyDecisions->when;
    }
    
    protected function lifeSustaining()
    {
        return $this->lpa->document->primaryAttorneyDecisions->canSustainLife;
    }
    
    protected function primaryAttorney()
    {
        return $this->lpa->document->primaryAttorneys;
    }
    
    protected function howPrimaryAttorneysMakeDecision()
    {
        return $this->lpa->document->primaryAttorneyDecisions->how;
    }
    
    protected function replacementAttorney()
    {
        return $this->lpa->document->replacementAttorneys;
    }
    
    protected function whenReplacementAttorneyStepIn()
    {
        return $this->lpa->document->replacementAttorneyDecisions->when;
    }
    
    protected function howReplacementAttorneysMakeDecision()
    {
        return $this->lpa->document->replacementAttorneyDecisions->how;
    }
    
    protected function certificateProvider()
    {
        return $this->lpa->document->certificateProvider->name;
    }
    
    protected function peopleToNotify()
    {
        return $this->lpa->document->peopleToNotify;
    }
    
    protected function instructions()
    {
        return "Review";
    }
    
    protected function applicant()
    {
        if($this->lpa->document->whoIsRegistering == 'donor') {
            $donor = $this->lpa->document->donor->name;
            return ['who'=>'donor', 'name'=>$donor->title.' '.$donor->first.' '.$donor->last];
        }
        else {
            $names = [];
            foreach($this->lpa->document->whoIsRegistering as $attorneyIdx) {
                $attorney = $this->lpa->document->primaryAttorneys[attorneyIdx]->name;
                $names[] = $attorney->title.' '.$attorney->first.' '.$attorney->last;
            }
            return ['who'=>'attorney', 'name'=>$names];
        }
    }
    
    protected function correspondent()
    {
        return $this->lpa->document->correspondent->name;
    }
    
    protected function whatIsMyRole()
    {
        return "Who was using the LPA tool answered";
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
