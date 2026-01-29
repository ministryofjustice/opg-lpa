<?php

declare(strict_types=1);

namespace Application\Service;

use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\View\Model\NavigationViewModel;
use MakeShared\DataModel\User\User;

class NavigationViewModelHelper
{
    public function __construct(private SessionUtility $sessionUtility)
    {
    }

    public function build(string $currentRoute): NavigationViewModel
    {
        $name = '';
        $lastLoginAt = null;
        $hasOneOrMoreLPAs = false;

        $user = $this->sessionUtility->getFromMvc(ContainerNamespace::USER_DETAILS, 'user');

        if ($user instanceof User) {
            $sessionUserName = $user->getName();
            if ($sessionUserName !== null) {
                $name = $sessionUserName->getFirst() . ' ' . $sessionUserName->getLast();
            }

            $lastLoginAt = $user->getLastLoginAt();
            $hasOneOrMoreLPAs = $user->getNumberOfLpas() > 0;
        }

        return new NavigationViewModel(
            userLoggedIn: $user instanceof User,
            name: $name,
            lastLoginAt: $lastLoginAt,
            route: $currentRoute,
            hasOneOrMoreLPAs: $hasOneOrMoreLPAs,
        );
    }
}
