<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Zend\Http\Response as HttpResponse;
use Zend\View\Model\ViewModel;

class RegisterController extends AbstractBaseController
{
    /**
     * Register a new account.
     *
     * @return ViewModel|\Zend\Http\Response
     */
    public function indexAction()
    {
        $request = $this->getRequest();

        //  gov.uk is not allowed to point users directly at this page
        $referer = $request->getHeader('Referer');

        if ($referer != false) {
            if ($referer->uri()->getHost() === 'www.gov.uk') {
                return $this->redirect()->toRoute('home');
            }
        }

        $response = $this->preventAuthenticatedUser();

        if ($response instanceof HttpResponse) {
            //  The user is already logged in so log a message and then
            $identity = $this->getServiceLocator()
                             ->get('AuthenticationService')
                             ->getIdentity();

            $this->log()->info('Authenticated user attempted to access registration page', $identity->toArray());

            return $response;
        }

        $form = $this->getServiceLocator()
                     ->get('FormElementManager')
                     ->get('Application\Form\User\Registration');
        $form->setAttribute('action', $this->url()->fromRoute($currentRoute = $this->getEvent()->getRouteMatch()->getMatchedRouteName()));

        $viewModel = new ViewModel();
        $viewModel->form = $form;

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();

                $result = $this->getServiceLocator()
                               ->get('Register')
                               ->registerAccount(
                                   $data['email'],
                                   $data['password']
                               );

                if ($result === true) {
                    $viewModel->email = $data['email'];
                    $viewModel->setTemplate('application/register/email-sent');
                } else {
                    $viewModel->error = $result;
                }
            }
        }

        return $viewModel;
    }

    /**
     * Confirm the email address, activating the account.
     *
     * @return ViewModel
     */
    public function confirmAction()
    {
        $token = $this->params()->fromRoute('token');

        if (empty($token)) {
            return new ViewModel([
                'error' => 'invalid-token'
            ]);
        }

        // Ensure they're not logged in whilst activating a new account.
        $this->getServiceLocator()
             ->get('AuthenticationService')
             ->clearIdentity();

        $session = $this->getServiceLocator()
                        ->get('SessionManager');
        $session->getStorage()->clear();
        $session->initialise();

        //  Returns true if the user account exists and the account was activated
        //  Returns false if the user account does not exist
        $success = $this->getServiceLocator()
                        ->get('Register')
                        ->activateAccount($token);

        $viewModel = new ViewModel();

        if (!$success) {
            $viewModel->error = 'account-missing';
        }

        return $viewModel;
    }
}
