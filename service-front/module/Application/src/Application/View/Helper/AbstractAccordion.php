<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;
use Opg\Lpa\DataModel\Lpa\Lpa;

abstract class AbstractAccordion extends AbstractHelper
{
    private $bars = [
        'creation' => [
            'lpa/form-type' => [
                    'active'    => 'What type of LPA do you want to create?',
                    'inactive'  => 'type',
            ],
            'lpa/donor' => [
                    'active'    => 'Who is the donor for this LPA?',
                    'inactive'  => 'donor',
            ],
            'lpa/when-lpa-starts' => [
                    'active'    => 'When can your LPA be used?',
                    'inactive'  => "whenLpaStarts",
            ],
            'lpa/life-sustaining' => [
                    'active'    => 'Can your attorneys give or refuse consent to life-sustaining treatment?',
                    'inactive'  => "lifeSustaining",
            ],
            'lpa/primary-attorney' => [
                    'active'    => 'Who are your attorneys?',
                    'inactive'  => 'attorney',
            ],
            'lpa/how-primary-attorneys-make-decision' => [
                    'active'    => 'How should your attorneys make decisions?',
                    'inactive'  => 'howPrimaryAttorneysMakeDecision',
            ],
            'lpa/replacement-attorney' => [
                    'active'    => 'Do you want any replacement attorneys?',
                    'inactive'  => 'replacementAttorney',
            ],
            'lpa/when-replacement-attorney-step-in' => [
                    'active'    => 'How should your replacement attorneys step in?',
                    'inactive'  => 'whenReplacementAttorneyStepIn',
            ],
            'lpa/how-replacement-attorneys-make-decision' => [
                    'active'    => 'How should your replacement attorneys make decisions?',
                    'inactive'  => 'howReplacementAttorneysMakeDecision',
            ],
            'lpa/certificate-provider' => [
                    'active'    => 'Who is the certificate provider?',
                    'inactive'  => 'certificateProvider',
            ],
            'lpa/people-to-notify' => [
                    'active'    => 'Who should be told before your LPA is registered?',
                    'inactive'  => 'peopleToNotify',
            ],
            'lpa/instructions' => [
                    'active'    => 'LPA created',
                    'inactive'  => 'instructions',
            ],
        ],
        'registration' => [
            'lpa/applicant' => [
                    'active'    => 'Who’s applying to register the LPA?',
                    'inactive'  => 'applicant',
            ],
            'lpa/correspondent' => [
                    'active'    => 'Where should we send the LPA and any correspondence?',
                    'inactive'  => 'correspondent',
            ],
            'lpa/what-is-my-role' => [
                    'active'    => 'Who was using the LPA tool?',
                    'inactive'  => 'whatIsMyRole',
            ],
            'lpa/fee' => [
                    'active'    => 'Payment',
                    'inactive'  => 'fee',
            ],
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
        return 'This LPA covers property and financial affairs';
    }
    
    protected function donor()
    {
        return 'The donor is ';
    }
    
    protected function whenLpaStarts()
    {
        return "The LPA can be used as soon as it's registered (with my consent)";
    }
    
    protected function lifeSustaining()
    {
        return "The attorneys can make decisions about life-sustaining treatment on the donor's behalf";
    }
    
    protected function attorney()
    {
        return "The attorney/s is/are ";
    }
    
    protected function howPrimaryAttorneysMakeDecision()
    {
        return "The attorneys will act jointly and severally";
    }
    
    protected function replacementAttorney()
    {
        return "The replacement attorney is Ms Isobel Samantha Ward";
    }
    
    protected function whenReplacementAttorneyStepIn()
    {
        return "The replacement attorneys will step in as soon as one/none of the original attorneys can no longer act/act";
    }
    
    protected function howReplacementAttorneysMakeDecision()
    {
        return "The replacement attorneys will act jointly and severally";
    }
    
    protected function certificateProvider()
    {
        return "The replacement attorney is Ms Isobel Samantha Ward";
    }
    
    protected function peopleToNotify()
    {
        return "The person to be told is Sir Anthony Webb";
    }
    
    protected function instructions()
    {
        return "Review";
    }
    
    protected function applicant()
    {
        return "The LPA will be registered by the donor - Mrs Louise Mary James";
    }
    
    protected function correspondent()
    {
        return "The LPA will be sent to Mrs Louise Mary James";
    }
    
    protected function whatIsMyRole()
    {
        return "Who was using the LPA tool answered";
    }
    
    protected function fee()
    {
        return "Payment";
    }
    
}

?>