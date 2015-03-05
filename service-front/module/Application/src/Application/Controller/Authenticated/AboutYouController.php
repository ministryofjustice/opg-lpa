<?php

namespace Application\Controller\Authenticated;

use Zend\Session\Container as SessionContainer;
use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

use Application\Form\User\AboutYou as AboutYouForm;

class AboutYouController extends AbstractAuthenticatedController {

    /**
     * Allow access to this controller before About You details are set.
     *
     * @var bool
     */
    protected $excludeFromAboutYouCheck = true;

    
    public function newAction(){

        $service = $this->getServiceLocator()->get('AboutYouDetails');

        //---

        $form = new AboutYouForm();
        $form->setAttribute( 'action', $this->url()->fromRoute('user/about-you/new') );

        $form->setData( $service->load()->flatten() );

        $error = null;

        //---

        $request = $this->getRequest();

        if ($request->isPost()) {

            $form->setData($request->getPost());


            if ($form->isValid()) {

                $user = $service->updateAllDetails( $form, $this->getUser() );

                $this->clearUserFromSession();

                // Direct them
                return $this->redirect()->toRoute( 'user/dashboard' );

            } // if

        } // if

        //---

        $pageTitle = 'Your Details';

        return new ViewModel( compact( 'form', 'error', 'pageTitle' ) );

    }

    /**
     * Clear the user details from the session.
     * They will be reloaded the next time the the AbstractAuthenticatedController is called.
     */
    private function clearUserFromSession(){

        // Store the details in the session...
        $detailsContainer = new SessionContainer('UserDetails');
        unset($detailsContainer->user);

    }

} // class
