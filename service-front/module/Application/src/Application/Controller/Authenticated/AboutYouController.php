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

        return new ViewModel($result);

    } // function

    //-------------------------

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

        return compact( 'form', 'error') ;

    } // function

} // class
