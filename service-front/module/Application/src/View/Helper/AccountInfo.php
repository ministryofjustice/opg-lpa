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
        if (!$auth->hasIdentity()) {
            return;
        }

        $params = [
            'view' => $this->view,
        ];

        //---

        $details = $serviceLocator->get('UserDetailsSession');

        // Only include name (and singed in user links) if the user has set their name.
        // i.e. they've completed the first About You step.
        if (isset($details->user) && $details->user->name !== null) {
            if ($details->user->name->first != null && $details->user->name->last != null) {
                $params['name'] = "{$details->user->name->first} {$details->user->name->last}";
            }
        }

        //-----------------------------------------------------
        // Include last logged in date if set a view parameter

        $layoutChildren = $serviceLocator->get('ViewManager')->getViewModel()->getIterator();

        if ($layoutChildren->count() > 0) {
            $view = $layoutChildren->current();

            if (isset($view->user) && isset($view->user['lastLogin'])) {
                $params['lastLogin'] = $view->user['lastLogin'];
            }
        }

        //---

        // Include the name of the current route.
        $routeMatch = $serviceLocator->get('Application')->getMvcEvent()->getRouteMatch();

        if ($routeMatch) {
            $params['route'] = $routeMatch->getMatchedRouteName();
        }

        //---------------------------------------------
        // Check if the user has one or more LPAs

        // Once a user has more than one, we cache the result in the session to save a lookup for every page load.
        if (!isset($details->hasOneOrMoreLPAs) || $details->hasOneOrMoreLPAs == false) {
            $lpasSummaries = $serviceLocator->get('LpaApplicationService')->getLpaSummaries();
            $details->hasOneOrMoreLPAs = ($lpasSummaries['total'] > 0);
        }

        $params['hasOneOrMoreLPAs'] = $details->hasOneOrMoreLPAs;

        //---

        $template = $serviceLocator->get('TwigViewRenderer')->loadTemplate('account-info/account-info.twig');

        echo $template->render($params);
    }
}
