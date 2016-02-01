<?php

namespace Application\Controller\Authenticated;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractAuthenticatedController;

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

    //-------------------------

    /**
     * User to set the About Me details for a newly registered user.
     *
     * @return \Zend\Http\Response|ViewModel
     */
    public function newAction(){

        /**
         * This imports a user's details from v1.
         * If successful, we do not show the v1 About You form to the user.
         *
         * When removing v1, the whole if statement below can be deleted.
         *
         * #v1Code
         */
        if( $this->getServiceLocator()->has('ProxyAboutYou') ){

            $email = (string)$this->getUserDetails()->email;

            try {

                $v1Details = $this->getServiceLocator()->get('ProxyAboutYou');

                // This returns true iff we have details we can import.
                $hasDetails = $v1Details->hasValidDetails( $email );

                if( $hasDetails === true ){

                    $this->getServiceLocator()->get('AboutYouDetails')->updateAllDetails( $v1Details );

                    // Clear the old details out the session.
                    // They will be reloaded the next time the the AbstractAuthenticatedController is called.
                    $detailsContainer = $this->getServiceLocator()->get('UserDetailsSession');
                    unset($detailsContainer->user);

                    return $this->redirect()->toRoute( 'user/dashboard' );

                } // if

            } catch( \Exception $e ){

                // Don't do anything, the user will just continue to the v2 form.

            } // try

        } // if

        // end #v1Code

        //----

        $result = $this->process();

        if( $result === true ){
            return $this->redirect()->toRoute( 'user/dashboard' );
        }

        //---

        $result['form']->setAttribute( 'action', $this->url()->fromRoute('user/about-you/new') );

        return $result;

    } // function

    //--------------------------------------------

    /**
     * Create and return the Form.
     *
     * @return array|bool
     */
    private function process(){

        $service = $this->getServiceLocator()->get('AboutYouDetails');

        //---

        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\User\AboutYou');

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
