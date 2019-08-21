<?php

namespace Application\Controller\General;

use Application\Controller\AbstractBaseController;
use Application\Form\User\Login as LoginForm;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Zend\Http\Response;
use Zend\Session\Container;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class AuthController extends AbstractBaseController
{
    /**
     * @var LpaApplicationService
     */
    private $lpaApplicationService;

    /**
     * @return bool|\Zend\Http\Response|ViewModel
     */
    public function indexAction()
    {
        $check = $this->preventAuthenticatedUser();

        if ($check !== true) {
            return $check;
        }

        $check = $this->checkCookie('login');

        if ($check !== true) {
            return $check;
        }

        // Create an instance of the login form
        $form = $this->getLoginForm();

        $authError = null;

        if ($this->request->isPost()) {
            $form->setData($this->request->getPost());

            if ($form->isValid()) {
                // Check if we're going to redirect to a deep(er) link (before we kill the session)
                $preAuthRequest = new Container('PreAuthRequest');

                if ($preAuthRequest->url) {
                    $nextUrl = $preAuthRequest->url;
                }

                // Ensure no user is logged in and ALL session data is cleared then re-initialise it.
                $session = $this->getSessionManager();

                $session->getStorage()->clear();
                $session->initialise();

                $email = $form->getData()['email'];
                $password = $form->getData()['password'];

                //  Perform the authentication with the user email and password
                $result = $this->getAuthenticationService()
                               ->setEmail($email)
                               ->setPassword($password)
                               ->authenticate();

                // If all went well...
                if ($result->isValid()) {
                    // Regenerate the session ID post authentication
                    $session->regenerateId(true);

                    // is there a return url stored in the session?
                    if (isset($nextUrl)) {
                        $pathArray = explode("/", parse_url($nextUrl, PHP_URL_PATH));

                        //  Does that url refer to an LPA?
                        if (count($pathArray) > 2 && $pathArray[1] == "lpa" && is_numeric($pathArray[2])) {
                            //  It does but check if the requested URL is the date check page
                            if (isset($pathArray[3]) && $pathArray[3] == 'date-check') {
                                return $this->redirect()->toUrl($nextUrl);
                            }

                            //  Redirect to next page which needs filling out
                            $lpaId = $pathArray[2];
                            $lpa = $this->lpaApplicationService->getApplication(
                                (int)$lpaId,
                                $result->getIdentity()->token()
                            );

                            if ($lpa instanceof Lpa) {
                                $formFlowChecker = new FormFlowChecker($lpa);
                                $destinationRoute = $formFlowChecker->backToForm();

                                return $this->redirect()->toRoute($destinationRoute, ['lpa-id' => $lpa->id], $formFlowChecker->getRouteOptions($destinationRoute));
                            }
                        }

                        //not an LPA url so redirect directly to it
                        return $this->redirect()->toUrl($nextUrl);
                    }

                    //  If necessary set a flash message showing that the user account will now remain active
                    if (in_array('inactivity-flags-cleared', $result->getMessages())) {
                        $this->flashMessenger()->addWarningMessage('Thanks for logging in. Your LPA account will stay open for another 9 months.');
                    }

                    // Else Send them to the dashboard...
                    return $this->redirect()->toRoute('user/dashboard');
                }

                // else authentication failed...

                // Create a new instance of the login form as we'll need a new Csrf token
                $form = $this->getLoginForm();

                // Keep the entered email address
                $form->setData([
                    'email' => $email
                ]);

                $authError = $result->getMessages();

                //  If there is a message, extract it (there will only ever be one).
                if (is_array($authError) && count($authError) > 0) {
                    $authError = array_pop($authError);
                }

                //  Help mitigate brute force attacks
                sleep(1);
            }
        }

        $isTimeout = ( $this->params('state') == 'timeout' );

        return new ViewModel([
            'form' => $form,
            'authError' => $authError,
            'isTimeout' => $isTimeout
        ]);
    }

    /**
     * Returns a new instance of the login form.
     *
     * @return LoginForm
     */
    private function getLoginForm()
    {
        $form = $this->getFormElementManager()->get('Application\Form\User\Login');

        /** @var $form LoginForm */
        $form->setAttribute('action', $this->url()->fromRoute('login'));

        return $form;
    }

    /**
     * Get session state without refreshing the session
     *
     * @return JsonModel|Response
     * for live session, otherwise 204
     */
    public function sessionExpiryAction()
    {
        $remainingSeconds = $this->getAuthenticationService()->getSessionExpiry();

        if (!$remainingSeconds) {
            $response =  new Response();
            $response->setStatusCode(204);
            return $response;
        }

        return new JsonModel(['remainingSeconds' => $remainingSeconds]);
    }

    /**
     * Logs the user out by clearing the identity from the session.
     *
     * @return \Zend\Http\Response
     */
    public function logoutAction()
    {
        $this->clearSession();

        return $this->redirect()->toUrl($this->config()['redirects']['logout']);
    }

    /**
     * Wipes all session details post-account deletion.
     *
     * @return ViewModel
     */
    public function deletedAction()
    {
        $this->clearSession();

        return new ViewModel();
    }

    /**
     * Destroys the current session.
     */
    private function clearSession()
    {
        $this->getAuthenticationService()->clearIdentity();

        $this->getSessionManager()->destroy([
            'clear_storage' => true
        ]);
    }

    /**
     * @param LpaApplicationService $lpaApplicationService
     */
    public function setLpaApplicationService(LpaApplicationService $lpaApplicationService)
    {
        $this->lpaApplicationService = $lpaApplicationService;
    }
}
