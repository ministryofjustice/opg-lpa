<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Model\Service\User\Details as UserService;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Http\Response as HttpResponse;
use Laminas\View\Model\ViewModel;

class ForgotPasswordController extends AbstractBaseController
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * GET: Display's the 'Enter your email address' form.
     * POST: Sends the password reset email.
     *
     * Laminas indexAction is not supposed to return false or HttpResponse,
     * but Laminas doesn't mind if that's what is returned...
     * @psalm-suppress ImplementedReturnTypeMismatch
     *
     * @return HttpResponse|ViewModel|false
     */
    public function indexAction()
    {
        $check = $this->preventAuthenticatedUser();

        if ($check !== true) {
            return $check;
        }

        $form = $this->getFormElementManager()->get('Application\Form\User\ConfirmEmail');
        $form->setAttribute('action', $this->url()->fromRoute('forgot-password'));

        $error = null;

        $request = $this->convertRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $result = $this->userService->requestPasswordResetEmail($form->getData()['email']);

                //We do not want to confirm or deny the existence of a registered user so do not check the result.
                //Exceptions would still be propagated
                $viewParams = [
                    'email' => $form->getData()['email'],
                    'accountNotActivated' => ($result === 'account-not-activated'),
                ];

                return (new ViewModel($viewParams))->setTemplate(
                    'application/general/forgot-password/email-sent.twig'
                );
            }
        }

        return new ViewModel(
            array_merge(
                compact('form', 'error')
            )
        );
    }

    /**
     * GET: Displays the 'Enter new password' form.
     * POST: Sets the new password.
     *
     * @return ViewModel|RedirectResponse
     *
     * Laminas HTTP responses have methods on which are not
     * defined on the interface they say they return
     *
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function resetPasswordAction()
    {
        $token = $this->params()->fromRoute('token');

        if (empty($token)) {
            return (new ViewModel())->setTemplate('application/general/forgot-password/invalid-reset-token.twig');
        }

        $identity = $this->getAuthenticationService()->getIdentity();

        // If there's currently a signed in user...
        if (!is_null($identity)) {
            /// Log them out...
            $session = $this->getSessionManager();
            $session->getStorage()->clear();
            $this->sessionManagerSupport->initialise();

            // Then redirect the user to the same page, now signed out, and with a new CSRF token.
            return $this->redirectToRoute('forgot-password/callback', ['token' => $token]);
        }

        // We have a valid reset token...
        $form = $this->getFormElementManager()->get('Application\Form\User\SetPassword');
        $form->setAttribute(
            'action',
            $this->url()->fromRoute('forgot-password/callback', ['token' => $token])
        );

        $error = null;

        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $result = $this->userService->setNewPassword($token, $form->getData()['password']);

                // if all good, direct them back to login.
                if ($result === true) {
                    /**
                     * psalm doesn't understand Laminas MVC plugins
                     *
                     * @psalm-suppress UndefinedMagicMethod
                     * */
                    $this->flashMessenger()->addSuccessMessage('Password successfully reset');

                    // Send them to login...
                    return $this->redirectToRoute('login');
                }

                if ($result == 'invalid-token') {
                    return (new ViewModel())->setTemplate(
                        'application/general/forgot-password/invalid-reset-token.twig'
                    );
                }

                // else there was an error
                $error = $result;
            }
        }

        return new ViewModel(
            array_merge(
                compact('form', 'error')
            )
        );
    }

    public function setUserService(UserService $userService)
    {
        $this->userService = $userService;
    }
}
