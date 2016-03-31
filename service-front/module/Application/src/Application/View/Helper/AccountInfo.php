<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class AccountInfo extends AbstractHelper
{
    public function __invoke()
    {
        $serviceLocator = $this->view->getHelperPluginManager()->getServiceLocator();
        
        $auth = $serviceLocator->get('AuthenticationService');

        // Only continue if the user is singed in.
        if ( !$auth->hasIdentity() ){ return; }
        
        $params = [
            'view' => $this->view,
        ];

        //---

        $details = $this->view->getHelperPluginManager()->getServiceLocator()->get('UserDetailsSession');

        // Only include name (and singed in user links) if the user has set their name.
        // i.e. they've completed the first About You step.
        if($details->user->name !== null) {

            if( $details->user->name->first != null && $details->user->name->last != null ){
                $params['name'] = "{$details->user->name->first} {$details->user->name->last}";
            }

        }

        //-----------------------------------------------------
        // Include last logged in date if set a view parameter

        $layoutChildren = $this->view->getHelperPluginManager()->getServiceLocator()
            ->get('ViewManager')->getViewModel()->getIterator();

        if( $layoutChildren->count() > 0 ){
            $view = $layoutChildren->current();

            if( isset($view->user) && isset($view->user['lastLogin']) ){
                $params['lastLogin'] = $view->user['lastLogin'];
            }

        } // if

        //---

        // Include the name of the current route.
        $params['route'] = $serviceLocator->get('Application')->getMvcEvent()->getRouteMatch()->getMatchedRouteName();

        //---

        $template = $this->view->getHelperPluginManager()->getServiceLocator()->get('TwigViewRenderer')->loadTemplate('account-info/account-info.twig');

        echo $template->render($params);

    } // function

} // class
