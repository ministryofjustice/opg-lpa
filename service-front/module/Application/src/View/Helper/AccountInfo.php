<?php

namespace Application\View\Helper;

use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionUtility;
use MakeShared\DataModel\User\User;
use Laminas\Router\RouteMatch;
use Laminas\View\Helper\AbstractHelper;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

class AccountInfo extends AbstractHelper implements LoggerAwareInterface
{
    use LoggerTrait;

    /** @var AuthenticationService */
    private $authenticationService;

    /** @var SessionUtility */
    private $sessionUtility;

    /** @var ViewModel */
    private $viewModel;

    /** @var RouteMatch */
    private $routeMatch;

    /** @var LpaApplicationService */
    private $lpaApplicationService;

    /** @var LocalViewRenderer */
    private $localViewRenderer;

    /**
     * @param AuthenticationService $authenticationService
     * @param SessionUtility $sessionUtility
     * @param ViewModel $viewModel
     * @param RouteMatch $routeMatch
     * @param LpaApplicationService $lpaApplicationService
     */
    public function __construct(
        AuthenticationService $authenticationService,
        SessionUtility $sessionUtility,
        ViewModel $viewModel,
        ?RouteMatch $routeMatch,
        LpaApplicationService $lpaApplicationService,
        LocalViewRenderer $localViewRenderer
    ) {
        $this->authenticationService = $authenticationService;
        $this->sessionUtility = $sessionUtility;
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
        $user = $this->sessionUtility->getFromMvc('UserDetails', 'user');
        if ($user instanceof User) {
            $sessionUserName = $user->getName();
            if ($sessionUserName !== null) {
                $params['name'] = $sessionUserName->getFirst() . ' ' . $sessionUserName->getLast();
            } else {
                $params['name'] = '';
            }
        }

        //  Include last logged in date if set a view parameter
        /** @var \Iterator $layoutChildren */
        $layoutChildren = $this->viewModel->getIterator();

        if ($this->viewModel->count() > 0) {
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
            !$this->sessionUtility->hasInMvc('UserDetails', 'hasOneOrMoreLPAs') ||
            $this->sessionUtility->getFromMvc('UserDetails', 'hasOneOrMoreLPAs') == false
        ) {
            $lpasSummaries = $this->lpaApplicationService->getLpaSummaries();
            $this->sessionUtility->setInMvc(
                'UserDetails',
                'hasOneOrMoreLPAs',
                (array_key_exists('total', $lpasSummaries) && $lpasSummaries['total'] > 0)
            );
        }

        $params['hasOneOrMoreLPAs'] = $this->sessionUtility->getFromMvc('UserDetails', 'hasOneOrMoreLPAs');

        echo $this->localViewRenderer->renderTemplate('account-info/account-info.twig', $params);
    }
}
