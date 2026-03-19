<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Model\FormFlowChecker;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Application\Model\Service\Authentication\Identity\User as IdentityUser;

/**
 * Constants for parameter keys used to pass data between event listeners
 * and handlers within the application's event system.
 */
class EventParameter
{
    public const string LPA = Lpa::class;
    public const string FLOW_CHECKER = FormFlowChecker::class;
    public const string CURRENT_ROUTE = 'currentRouteName';
    public const string USER_DETAILS = User::class;
    public const string IDENTITY = IdentityUser::class;
}
