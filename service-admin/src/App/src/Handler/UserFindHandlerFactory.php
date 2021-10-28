<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\User\UserService;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class UserFindHandlerFactory
 * @package App\Handler
 */
class UserFindHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        $userSearchService = $container->get(UserService::class);

        return new UserFindHandler($userSearchService);
    }
}
