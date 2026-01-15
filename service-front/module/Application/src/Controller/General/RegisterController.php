<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Http\Header\Referer;
use Laminas\Http\Response as HttpResponse;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;

class RegisterController extends AbstractBaseController
{
    use LoggerTrait;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * Register a new account.
     *
     * The Laminas MVC controller only allows ViewModel
     * as a return type, but we return a redirect response in
     * some cases. (Not sure why Laminas doesn't enforce this
     * when sending responses to the client from an MVC controller,
     * but it doesn't.) So we suppress this psalm error.
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @return ViewModel|RedirectResponse
     */
    public function indexAction()
    {
        $request = $this->convertRequest();

        $ga = $request->getQuery('_ga');

        // gov.uk is not allowed to point users directly at this page
        /** @var Referer */
        $referer = $request->getHeader('Referer');

        // despite the implicit cast above, $referer might be a GenericHeader
        // if the URI has an invalid scheme like android-app://, hence the is_a() check;
        // otherwise $referer->uri() might throw a "method does not exist exception";
        // see LPAL-1151
        if (
            is_a($referer, Referer::class) &&
            (stripos($referer->uri()->getHost(), 'www.gov.uk') !== false)
        ) {
            return $this->redirectToRoute(
                'home',
                ['action' => 'index'],
                ['query' => ['_ga' => $ga]]
            );
        }

        $response = $this->preventAuthenticatedUser();

        if ($response instanceof HttpResponse) {
            //  The user is already logged in so log a message and then
            $identity = $this->getAuthenticationService()->getIdentity();

            $this->getLogger()->info('Authenticated user attempted to access registration page', [
                'identity' => $identity->toArray()
            ]);

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
     * @return ViewModel|false|\Laminas\Http\Response
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

        $request = $this->convertRequest();

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
        $this->sessionManagerSupport->initialise();

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
