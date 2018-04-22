<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;

/**
 * @deprecated
 */
class LayoutPartial extends AbstractHelper
{
    public function __invoke ()
    {
        $serviceManager = $this->getView()->getHelperPluginManager()->getServiceLocator();
        $serviceManager instanceof ServiceManager;
        
        $application = $serviceManager->get('application');
        $routeName = $application->getMvcEvent()->getRouteMatch()->getMatchedRouteName();
        
        if(in_array($routeName, [
                'lpa/certificate-provider',
                'lpa/donor',
                'lpa/donor/add',
                'lpa/donor/edit',
                'lpa/form-type',
                'lpa/how-primary-attorneys-make-decision',
                'lpa/how-replacement-attorneys-make-decision',
                'lpa/instructions',
                'lpa/life-sustaining',
                'lpa/people-to-notify',
                'lpa/people-to-notify/add',
                'lpa/people-to-notify/edit',
                'lpa/primary-attorney',
                'lpa/primary-attorney/add',
                'lpa/primary-attorney/edit',
                'lpa/replacement-attorney',
                'lpa/replacement-attorney/add',
                'lpa/replacement-attorney/edit',
                'lpa/when-lpa-starts',
                'lpa/when-replacement-attorney-step-in',
        ])) {
            return $this->view->partial('layout/creation-partial.phtml');
        }
        elseif(in_array($routeName, [
                'lpa/applicant',
                'lpa/complete',
                'lpa/correspondent',
                'lpa/fee',
                'lpa/who-are-you',
        ])) {
            return $this->view->partial('layout/registration-partial.phtml');
        }
        else {
            return '';
        }
    }
}
