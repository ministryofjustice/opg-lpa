<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Model\FormFlowChecker;
use Application\Model\Service\Authentication\Identity\User as IdentityUser;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;

/**
 * Constants for request attribute keys used to store and retrieve data
 * from PSR-7 request objects as they pass through the middleware pipeline
 * and Handlers.
 */
class RequestAttribute
{
    public const string LPA = Lpa::class;
    public const string FLOW_CHECKER = FormFlowChecker::class;
    public const string CURRENT_ROUTE_NAME = 'currentRouteName';
    public const string USER_DETAILS = User::class;
    public const string IDENTITY = IdentityUser::class;
}
