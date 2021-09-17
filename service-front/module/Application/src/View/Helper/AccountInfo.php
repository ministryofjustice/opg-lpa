<?php

namespace Application\View\Helper;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\User\User;
use Laminas\Router\RouteMatch;
use Laminas\Session\Container;
use Laminas\View\Helper\AbstractHelper;
use Laminas\View\Model\ViewModel;

class AccountInfo extends AbstractHelper
{
    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var Container
     */
    private $userDetailsSession;

    /**
     * @var ViewModel
     */
    private $viewModel;

    /**
     * @var RouteMatch
     */
    private $routeMatch;

    /**
     * @var LpaApplicationService
     */
    private $lpaApplicationService;

    /**
     * @var LocalViewRenderer
     */
    private $localViewRenderer;

    /**
     * @param AuthenticationService $authenticationService
     * @param Container $userDetailsSession
     * @param ViewModel $viewModel
     * @param RouteMatch $routeMatch
     * @param LpaApplicationService $lpaApplicationService
     */
    public function __construct(
        AuthenticationService $authenticationService,
        Container $userDetailsSession,
        ViewModel $viewModel,
        ?RouteMatch $routeMatch,
        LpaApplicationService $lpaApplicationService,
        LocalViewRenderer $localViewRenderer
    ) {
        $this->authenticationService = $authenticationService;
        $this->userDetailsSession = $userDetailsSession;
        $this->viewModel = $viewModel;
        $this->routeMatch = $routeMatch;
        $this->lpaApplicationService = $lpaApplicationService;
        $this->localViewRenderer = $localViewRenderer;
    }

    public function __invoke()
    {
        //  Only continue if the user is logged in
        if (!$this->authenticationService->hasIdentity()) {
            return;
        }

        $params = [
            'view' => $this->view,
        ];

        //  Only include name (and user links) if the user has set their name - i.e. they've completed the
        //  first About You step
        if ($this->userDetailsSession->user instanceof User & $this->userDetailsSession->user->name instanceof Name) {
            $sessionUserName = $this->userDetailsSession->user->getName();

            if ($sessionUserName instanceof Name) {
                $params['name'] = $sessionUserName->getFirst() . ' ' . $sessionUserName->getLast();
            }
        }

        //  Include last logged in date if set a view parameter
        $layoutChildren = $this->viewModel->getIterator();

        if ($layoutChildren->count() > 0) {
            $view = $layoutChildren->current();

            if (isset($view->user) && isset($view->user['lastLogin'])) {
                $params['lastLogin'] = $view->user['lastLogin'];
            }
        }

        //  Include the name of the current route
        if ($this->routeMatch) {
            $params['route'] = $this->routeMatch->getMatchedRouteName();
        }

        // Check if the user has one or more LPAs
        // Once a user has more than one, we cache the result in the session to save a lookup for every page load.
        if (
            !isset($this->userDetailsSession->hasOneOrMoreLPAs) ||
            $this->userDetailsSession->hasOneOrMoreLPAs == false
        ) {
            $lpasSummaries = $this->lpaApplicationService->getLpaSummaries();
            $this->userDetailsSession->hasOneOrMoreLPAs = ($lpasSummaries['total'] > 0);
        }

        $params['hasOneOrMoreLPAs'] = $this->userDetailsSession->hasOneOrMoreLPAs;

        echo $this->localViewRenderer->renderTemplate('account-info/account-info.twig', $params);
    }
}
