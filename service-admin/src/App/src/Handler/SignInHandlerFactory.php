<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Authentication\AuthenticationService;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use App\Service\User\UserService;

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
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        $authService = $container->get(AuthenticationService::class);
        $config = $container->get('config');
        $userSearchService = $container->get(UserService::class);

        return new SignInHandler($authService, $config['admin_accounts'], $userSearchService);
    }
}
