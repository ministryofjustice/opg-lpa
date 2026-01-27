<?php

namespace Application\Controller;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\SessionManager;
use Laminas\View\Model\JsonModel;
use MakeShared\Logging\LoggerTrait;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\View\Model\ViewModel;
use Psr\Log\LoggerAwareInterface;

abstract class AbstractBaseController extends AbstractActionController implements LoggerAwareInterface
{
    use LoggerTrait;

    public function __construct(
        private AbstractPluginManager $formElementManager,
        protected SessionManagerSupport $sessionManagerSupport,
        private AuthenticationService $authenticationService,
        private readonly array $config,
        protected SessionUtility $sessionUtility
    ) {
    }

    public function onDispatch(MvcEvent $e)
    {
        $currentRoute = $e->getRouteMatch()->getMatchedRouteName();

        $view = parent::onDispatch($e);

        if (($view instanceof ViewModel) && !($view instanceof JsonModel)) {
            $view->setVariable('currentRouteName', $currentRoute);
        }

        return $view;
    }

    /**
     * Convert the Laminas RequestInterface object provided
     * by $this->request into a full-fledged HttpRequest.
     * This is primarily so we don't have to add lots of
     * type conversions inline in child controllers.
     *
     * @return HttpRequest
     */
    protected function convertRequest()
    {
        /** @var HttpRequest */
        $request = $this->request;

        return $request;
    }

    /**
     * Define indexAction() return types here, as we typically
     * return HttpResponse objects which the Laminas MVC
     * AbstractActionController->indexAction() doesn't. This
     * results in a lot of psalm lint errors. However, it's
     * perfectly fine to return a HttpResponse from indexAction()
     * and the Laminas framework handles it appropriately.
     *
     * Where a subclass doesn't declare return types on indexAction(),
     * this declaration will be used instead, and avoid the lint errors.
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @return HttpResponse|ViewModel
     */
    public function indexAction()
    {
        return parent::indexAction();
    }

    /**
     * Ensures cookies are enabled.
     *
     * If we're passed the session cookies, we know they're enabled, so all is good.
     *
     * If we're not passed a session cookie, this could be because:
     *  A - The session simply has not been started; or
     *  B - They do not have cookies enabled.
     *
     * To rule out A, we redirect they user back to 'this' page, adding ?cookie=1 to the URL to record the
     * redirect has happened. This ensures the session *should* have been started.
     *
     * Thus is the session cookies doesn't exist AND cookie=1, we can assume the client is not sending cookies.
     *
     * @param $routeName string The route name for the current page for if a redirect is needed.
     * @return bool|\Laminas\Http\Response Iff bool true is returned,
     *     all is good. Otherwise the calling controller should return the response.
     */
    protected function checkCookie($routeName)
    {
        $request = $this->convertRequest();

        // Only do a cookie check on GETs
        if ($request->getMethod() !== 'GET') {
            return true;
        }

        // Get the cookie names used for the session
        $sessionCookieName = $this->config['session']['native_settings']['name'];

        $cookies = $request->getCookie();

        // If there cookies...
        if ($cookies !== false) {
            // Check for the session cookie...
            $cookieExists = $cookies->offsetExists($sessionCookieName);
        }

        if (!$cookies || !$cookieExists) {
            /*
             * Redirect them back to the same page, appending ?cookie=1 to the URL.
             *  A - If they have cookies enabled, they should now have the session cookie, so all is well.
             *  B - They still don't have the cookie, we can assume they have cookies disabled.
             */

            $cookieRedirect = (bool)$this->params()->fromQuery('cookie');

            if (!$cookieRedirect) {
                // Cannot see a cookie, so redirect them back to this page
                // (which will set one), ready to check again.
                return $this->redirect()->toRoute(
                    $routeName,
                    [],
                    ['query' => ['cookie' => '1']]
                );
            } else {
                // Cookie is not set even after we've done a redirect,
                // so assume the client doesn't support cookies.
                return $this->redirect()->toRoute('enable-cookie');
            }
        }

        return true;
    }

    /**
     * Checks if a user is logged in and redirects them to the dashboard if they are.
     *
     * This is used to prevent signed in users accessing pages they should not.
     *
     * e.g. login, register, etc.
     *
     * @return bool|\Laminas\Http\Response
     */
    protected function preventAuthenticatedUser()
    {
        $identity = $this->authenticationService->getIdentity();

        if (!is_null($identity)) {
            return $this->redirect()->toRoute('user/dashboard');
        }

        return true;
    }

    /**
     * @return AbstractPluginManager
     */
    protected function getFormElementManager(): AbstractPluginManager
    {
        return $this->formElementManager;
    }

    /**
     * @return SessionManager
     */
    protected function getSessionManager(): SessionManager
    {
        return $this->sessionManagerSupport->getSessionManager();
    }

    /**
     * @return AuthenticationService
     */
    protected function getAuthenticationService(): AuthenticationService
    {
        return $this->authenticationService;
    }

    /**
     * Returns the global config.
     *
     * @return array
     */
    protected function config(): array
    {
        return $this->config;
    }
}
