<?php

namespace Application\Controller\Authenticated;

use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

use Application\Form\User\AboutYou as AboutYouForm;

class AboutYouController extends AbstractAuthenticatedController {

    public function newAction(){

        $service = $this->getServiceLocator()->get('AboutYouDetails');

        //---

        $form = new AboutYouForm();
        $form->setAttribute( 'action', $this->url()->fromRoute('user/about-you') );

        $form->setData( $service->load()->flatten() );

        $error = null;

        //---

        $request = $this->getRequest();

        if ($request->isPost()) {

            $form->setData($request->getPost());


            if ($form->isValid()) {

                $user = $service->updateAllDetails( $form, $this->getUser() );

                $this->clearUserFromSession();

                $this->flashMessenger()->addSuccessMessage('Saved.');

                return $this->redirect()->toRoute( 'user/dashboard' );

            } // if

        } // if

        //---

        $pageTitle = 'Your Details';

        return new ViewModel( compact( 'form', 'error', 'pageTitle' ) );

    }

    private function clearUserFromSession(){

    }

} // class
