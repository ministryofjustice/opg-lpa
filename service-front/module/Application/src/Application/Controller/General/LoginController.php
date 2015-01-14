<?php

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class LoginController extends AbstractBaseController {

    public function indexAction(){

        $check = $this->checkCookie( 'login' );
        if( $check !== true ){ return $check; }

        //-----------------------

        $authenticationService = $this->getServiceLocator()->get('AuthenticationService');

        $authenticationAdapter = $this->getServiceLocator()->get('LpaApiClientAuthAdapter');

        $authenticationAdapter->setCredentials( 'neil.smith@digital.justice.gov.uk', 'xxx' );

        $result = $authenticationService->authenticate( $authenticationAdapter );

        return new ViewModel();
    }

}
