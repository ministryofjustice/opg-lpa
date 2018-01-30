<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Form\Validator\EmailAddress;
use Zend\Http\Response as HttpResponse;
use Zend\View\Model\ViewModel;
use Exception;

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
            $identity = $this->getAuthenticationService()->getIdentity();

            $this->getLogger()->info('Authenticated user attempted to access registration page', $identity->toArray());

            return $response;
        }

        $form = $this->getFormElementManager()
                     ->get('Application\Form\User\Registration');
        $form->setAttribute('action', $this->url()->fromRoute('register'));

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
                    //  Redirect to email sent route
                    return $this->redirect()->toRoute('register/email-sent', [], [
                        'query' => [
                            'email' => $data['email'],
                        ],
                    ]);
                } else {
                    $viewModel->error = $result;
                }
            }
        }

        return $viewModel;
    }

    /**
     * Display email sent page
     *
     * @return ViewModel
     * @throws Exception
     */
    public function emailSentAction()
    {
        $check = $this->preventAuthenticatedUser();

        if ($check !== true) {
            return $check;
        }

        $email = $this->params()->fromQuery('email');

        $emailValidator = new EmailAddress();

        if (is_null($email) || !$emailValidator->isValid($email)) {
            throw new Exception('Valid email address must be provided to view');
        }

        //  Set up a form so the resend can be triggered again easily from a link
        $form = $this->getFormElementManager()->get('Application\Form\User\ConfirmEmail');
        $form->setAttribute('action', $this->url()->fromRoute('register/resend-email'));

        $form->populateValues([
            'email'         => $email,
            'email_confirm' => $email,
        ]);

        return  new ViewModel([
            'email' => $email,
            'form'  => $form,
        ]);
    }

    /**
     * Display the form to resend the activation email or process a post
     *
     * @return ViewModel
     */
    public function resendEmailAction()
    {
        $check = $this->preventAuthenticatedUser();

        if ($check !== true) {
            return $check;
        }

        $form = $this->getFormElementManager()->get('Application\Form\User\ConfirmEmail');
        $form->setAttribute('action', $this->url()->fromRoute('register/resend-email'));

        $error = null;

        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();

                $result = $this->getServiceLocator()->get('Register')->resendActivateEmail($form->getData()['email']);

                //  We do not want to confirm or deny the existence of a registered user so do not check the result
                return $this->redirect()->toRoute('register/email-sent', [], [
                    'query' => [
                        'email' => $data['email'],
                    ],
                ]);
            }
        }

        return new ViewModel(
            array_merge(
                compact('form', 'error')
            )
        );
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
        $this->getAuthenticationService()->clearIdentity();

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
