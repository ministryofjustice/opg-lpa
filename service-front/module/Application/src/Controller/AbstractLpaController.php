<?php

namespace Application\Controller;

use Application\Model\FormFlowChecker;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\User\Details as UserService;
use MakeShared\DataModel\Lpa\Lpa;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\Session\Container;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;
use RuntimeException;

abstract class AbstractLpaController extends AbstractAuthenticatedController
{
    use LoggerTrait;

    /** @var LPA The LPA currently referenced in to the URL */
    private $lpa;

    /** @var \Application\Model\FormFlowChecker */
    private $flowChecker;

    /** @var ReplacementAttorneyCleanup */
    private $replacementAttorneyCleanup;

    /** @var Metadata */
    private $metadata;

    /**
     * AbstractLpaController constructor.
     * @param string $lpaId
     * @param AbstractPluginManager $formElementManager
     * @param SessionManagerSupport $sessionManagerSupport
     * @param AuthenticationService $authenticationService
     * @param array $config
     * @param Container $userDetailsSession
     * @param LpaApplicationService $lpaApplicationService
     * @param UserService $userService
     * @param ReplacementAttorneyCleanup $replacementAttorneyCleanup
     * @param Metadata $metadata
     */
    public function __construct(
        $lpaId,
        AbstractPluginManager $formElementManager,
        SessionManagerSupport $sessionManagerSupport,
        AuthenticationService $authenticationService,
        array $config,
        Container $userDetailsSession,
        LpaApplicationService $lpaApplicationService,
        UserService $userService,
        ReplacementAttorneyCleanup $replacementAttorneyCleanup,
        Metadata $metadata
    ) {
        parent::__construct(
            $formElementManager,
            $sessionManagerSupport,
            $authenticationService,
            $config,
            $userDetailsSession,
            $lpaApplicationService,
            $userService
        );

        // If there is no user identity the request will be bounced in the onDispatch function
        if ($authenticationService->hasIdentity()) {
            $lpa = $lpaApplicationService->getApplication((int) $lpaId);

            $this->lpa = $lpa;
            $this->replacementAttorneyCleanup = $replacementAttorneyCleanup;
            $this->metadata = $metadata;
        }
    }

    public function onDispatch(MvcEvent $e)
    {
        // Check we have a user set, thus ensuring an authenticated user
        if (($authenticated = $this->checkAuthenticated()) !== true) {
            return $authenticated;
        }

        if (!$this->lpa instanceof Lpa) {
            //404 error returned as either the LPA does not exist in the database, or is not associated with the user
            return $this->notFoundAction();
        }

        // If there is an identity then confirm that the LPA belongs to the user
        if ($this->getIdentity()->id() !== $this->lpa->user) {
            throw new RuntimeException('Invalid LPA - current user can not access it');
        }

        /** @var ViewModel */
        $layout = $this->layout();

        // inject lpa into layout
        $layout->lpa = $this->lpa;

        // check the requested route and redirect user to the correct one if the requested route is not available.
        $currentRoute = $e->getRouteMatch()->getMatchedRouteName();

        // get extra input query param from the request url.
        if ($currentRoute == 'lpa/download') {
            $param = $e->getRouteMatch()->getParam('pdf-type');
        } else {
            $param = $e->getRouteMatch()->getParam('idx');
        }

        // call flow checker to get the nearest accessible route.
        $calculatedRoute = $this->getFlowChecker()->getNearestAccessibleRoute($currentRoute, $param);

        // if false, do not run action method.
        if ($calculatedRoute === false) {
            return $this->response;
        }

        // redirect to the calculated route if it is not equal to the current route
        if ($calculatedRoute != $currentRoute) {
            return $this->redirect()->toRoute(
                $calculatedRoute,
                ['lpa-id' => $this->lpa->id],
                $this->getFlowChecker()->getRouteOptions($calculatedRoute)
            );
        }

        // inject lpa into view
        $view = parent::onDispatch($e);

        if (($view instanceof ViewModel) && !($view instanceof JsonModel)) {
            $view->setVariable('lpa', $this->lpa);
        }

        return $view;
    }

    /**
     * Return an appropriate view model to move to the next route from the current route
     *
     * @return ViewModel|\Laminas\Http\Response
     */
    protected function moveToNextRoute()
    {
        if ($this->isPopup()) {
            return new JsonModel(['success' => true]);
        }

        // Check that the route match is the correct type
        $routeMatch = $this->getEvent()->getRouteMatch();

        if (!$routeMatch instanceof RouteMatch) {
            throw new RuntimeException(
                'RouteMatch must be an instance of Laminas\Router\Http\RouteMatch for moveToNextRoute()'
            );
        }

        // Get the current route and the LPA ID to move to the next route
        $nextRoute = $this->getFlowChecker()->nextRoute($routeMatch->getMatchedRouteName());

        return $this->redirect()->toRoute(
            $nextRoute,
            ['lpa-id' => $this->lpa->id],
            $this->getFlowChecker()->getRouteOptions($nextRoute)
        );
    }

    /**
     * removes replacement attorney decisions that no longer apply to this LPA.
     *
     */
    protected function cleanUpReplacementAttorneyDecisions()
    {
        $this->replacementAttorneyCleanup->cleanUp($this->lpa);
    }

    /**
     * Return a flag indicating if this is a request from a popup (XmlHttpRequest)
     *
     * @return bool
     */
    protected function isPopup()
    {
        return $this->convertRequest()->isXmlHttpRequest();
    }

    /**
     * Returns the LPA currently referenced in to the URL
     *
     * @return Lpa
     */
    public function getLpa()
    {
        return $this->lpa;
    }

    /**
     * @return \Application\Model\FormFlowChecker
     */
    public function getFlowChecker()
    {
        if ($this->flowChecker == null) {
            $formFlowChecker = new FormFlowChecker($this->lpa);
            $this->flowChecker = $formFlowChecker;
        }

        return $this->flowChecker;
    }

    /**
     * Convert model/seed data for populating into form
     *
     * @param array $modelData - eg. [name=>[title=>'Mr', first=>'John', last=>'Smith']]
     * @return array - eg [name-title=>'Mr', name-first=>'John', name-last=>'Smith']
     */
    protected function flattenData($modelData)
    {
        $formData = [];

        foreach ($modelData as $l1 => $l2) {
            if (is_array($l2)) {
                foreach ($l2 as $name => $l3) {
                    if ($l1 == 'dob') {
                        $dob = new \DateTime($l3);
                        $formData['dob-date'] = [
                                'day'   => $dob->format('d'),
                                'month' => $dob->format('m'),
                                'year'  => $dob->format('Y'),
                        ];
                    } else {
                        $formData[$l1 . '-' . $name] = $l3;
                    }
                }
            } else {
                $formData[$l1] = $l2;
            }
        }

        return $formData;
    }

    /**
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->metadata;
    }
}
