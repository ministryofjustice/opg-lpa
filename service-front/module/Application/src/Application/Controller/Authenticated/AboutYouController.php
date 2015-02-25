<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

use Application\Form\User\AboutYou as AboutYouForm;

class AboutYouController extends AbstractAuthenticatedController {

    public function indexAction(){

        $form = new AboutYouForm();
        $form->setAttribute( 'action', $this->url()->fromRoute('user/about-you') );

        $error = null;

        //---

        $request = $this->getRequest();

        if ($request->isPost()) {

            $form->setData($request->getPost());

            if ($form->isValid()) {

                $this->flashMessenger()->addSuccessMessage('Saved. (Not really, but soon!)');

                return $this->redirect()->toRoute( 'user/dashboard' );

            } // if

        } // if

        //---

        $pageTitle = 'Your Details';

        return new ViewModel( compact( 'form', 'error', 'pageTitle' ) );

    }

} // class
