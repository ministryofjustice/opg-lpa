<?php

namespace Application\Controller\Authenticated;

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


    public function indexAction(){

        $result = $this->process();

        if( $result === true ){
            return $this->redirect()->toRoute( 'user/dashboard' );
        }

        //---

        $result['form']->setAttribute( 'action', $this->url()->fromRoute('user/about-you') );

        return $result;

    } // function

    /**
     * User to set the About Me details for a newly registered user.
     *
     * @return \Zend\Http\Response|ViewModel
     */
    public function newAction(){

        $result = $this->process();

        if( $result === true ){
            return $this->redirect()->toRoute( 'user/dashboard' );
        }

        //---

        $result['form']->setAttribute( 'action', $this->url()->fromRoute('user/about-you/new') );

        return $result;

    } // function

    private function process(){

        $service = $this->getServiceLocator()->get('AboutYouDetails');

        //---

        $form = new AboutYouForm();

        $form->setData( $service->load()->flatten() );

        $error = null;

        //---

        $request = $this->getRequest();

        if ($request->isPost()) {

            $form->setData($request->getPost());

            if ($form->isValid()) {

                $service->updateAllDetails( $form );

                // Clear the old details out the session.
                // They will be reloaded the next time the the AbstractAuthenticatedController is called.
                $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');
                unset($detailsContainer->user);

                // Save successful
                return true;

            } // if

        } // if

        //---

        $pageTitle = 'Your Details';

        return compact( 'form', 'error', 'pageTitle' ) ;

    } // function

} // class
