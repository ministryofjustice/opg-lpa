<?php

declare(strict_types=1);

namespace Application\Listener;

// Used to codify Laminas\Mvc\MvcEvent parameters and Psr\Http\Message\ServerRequestInterface attribute names
class Attribute
{
    // Contains MakeShared\DataModel\User
    public const string USER_DETAILS = 'userDetails';

    // Contains Application\Model\Service\Authentication\Identity\User
    public const string IDENTITY = 'identity';
}
