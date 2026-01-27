<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\View\Model\NavigationViewModel;
use MakeShared\DataModel\User\User;

class NavigationViewModelHelper
{
    public function __construct(
        private SessionUtility $sessionUtility,
        private LpaApplicationService $lpaApplicationService,
    ) {
    }

    public function build(string $currentRoute): NavigationViewModel
    {
        $name = '';
        $lastLoginAt = null;

        $user = $this->sessionUtility->getFromMvc(ContainerNamespace::USER_DETAILS, 'user');
        if ($user instanceof User) {
            $sessionUserName = $user->getName();
            if ($sessionUserName !== null) {
                $name = $sessionUserName->getFirst() . ' ' . $sessionUserName->getLast();
            }
            $lastLoginAt = $user->getLastLoginAt();
        }

        if (
            $user instanceof User &&
            (!$this->sessionUtility->hasInMvc(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs') ||
            $this->sessionUtility->getFromMvc(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs') === false)
        ) {
            $lpasSummaries = $this->lpaApplicationService->getLpaSummaries();
            $this->sessionUtility->setInMvc(
                ContainerNamespace::USER_DETAILS,
                'hasOneOrMoreLPAs',
                (array_key_exists('total', $lpasSummaries) && $lpasSummaries['total'] > 0)
            );
        }

        $hasOneOrMoreLPAs = $this->sessionUtility->getFromMvc(ContainerNamespace::USER_DETAILS, 'hasOneOrMoreLPAs') ?? false;

        return new NavigationViewModel(
            userLoggedIn: $user instanceof User,
            name: $name,
            lastLoginAt: $lastLoginAt,
            route: $currentRoute,
            hasOneOrMoreLPAs: $hasOneOrMoreLPAs,
        );
    }
}
