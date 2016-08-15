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
        ],
        'registration' => [
            'lpa/summary'                       => 'summary',
            'lpa/applicant'                     => 'applicant',
            'lpa/correspondent'                 => 'correspondent',
            'lpa/who-are-you'                   => 'whoAreYou',
            'lpa/repeat-application'            => 'repeatApplication',
            'lpa/fee-reduction'                 => 'feeReduction',
            //'lpa/payment'                       => 'payment',
            //'lpa/payment/summary'               => 'paymentSummary',
            //'lpa/payment/return/failure'        => null,
            //'lpa/payment/return/cancel'         => null,
        ],
    ];
    
    protected function getBarConfig ($routeName)
    {
        if ($routeName == 'lpa/payment/summary') {
            // No accordion summary when viewing table summary
            return [];
        }
        if ($routeName == 'lpa/summary') {
            // No accordion summary when viewing table summary
            return [];
        }
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
}
