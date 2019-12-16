<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\User\Details as UserService;
use Zend\Http\Response as HttpResponse;
use Zend\View\Model\ViewModel;

class RegisterController extends AbstractBaseController
{

    /**
     * @var UserService
     */
    private $userService;

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

        $ga = $request->getQuery('_ga');

        if ($referer != false) {
            if ($referer->uri()->getHost() === 'www.gov.uk') {
                return $this->redirect()->toRoute('home', ['action' => 'index'], ['query' => ['_ga' => $ga]]);
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

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $data = $form->getData();
                $email = $data['email'];
                $password = $data['password'];

                $result = $this->userService->registerAccount($email, $password);

                if ($result === true || $result == "address-already-registered") {
                    //  Change the view to be the email sent template with the email address and resend email form
                    //  Set up a form so the resend can be triggered again easily from a link
                    $form = $this->getFormElementManager()->get('Application\Form\User\ConfirmEmail');
                    $form->setAttribute('action', $this->url()->fromRoute('register/resend-email'));

                    $form->populateValues([
                        'email'         => $email,
                        'email_confirm' => $email,
                    ]);

                    $viewModel->setTemplate('application/general/register/email-sent.twig');
                    $viewModel->email = $email;
                } else {
                    $viewModel->error = $result;
                }
            }
        }

        //  Set the form before returning the view model
        $viewModel->form = $form;

        return $viewModel;
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

        $viewModel = new ViewModel();

        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $email = $form->getData()['email'];

                $result = $this->userService->resendActivateEmail($email);

                if ($result === true) {
                    //  Change the view to be the email sent template with the email address and resend email form
                    //  Set up a form so the resend can be triggered again easily from a link
                    $form = $this->getFormElementManager()->get('Application\Form\User\ConfirmEmail');
                    $form->setAttribute('action', $this->url()->fromRoute('register/resend-email'));

                    $form->populateValues([
                        'email'         => $email,
                        'email_confirm' => $email,
                    ]);

                    $viewModel->setTemplate('application/general/register/email-sent.twig');
                    $viewModel->email = $email;
                } else {
                    $viewModel->error = $result;
                }
            }
        }

        $viewModel->form = $form;

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
        $this->getAuthenticationService()->clearIdentity();

        $session = $this->getSessionManager();
        $session->getStorage()->clear();
        $session->initialise();

        //  Returns true if the user account exists and the account was activated
        //  Returns false if the user account does not exist
        $success = $this->userService->activateAccount($token);

        $viewModel = new ViewModel();

        if (!$success) {
            $viewModel->error = 'account-missing';
        }

        return $viewModel;
    }

    public function setUserService(UserService $userService)
    {
        $this->userService = $userService;
    }
}
