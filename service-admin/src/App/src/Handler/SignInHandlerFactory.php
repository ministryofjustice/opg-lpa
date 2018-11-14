<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class SignInHandlerFactory
 * @package App\Handler
 */
class SignInHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container) : RequestHandlerInterface
    {
        //  TODO - Pass in the auth service...
//        $authService = $container->get('?');

        return new SignInHandler();
    }
}
