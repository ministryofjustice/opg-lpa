<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\UserSearch\UserSearch;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class UserSearchHandlerFactory
 * @package App\Handler
 */
class UserSearchHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container) : RequestHandlerInterface
    {
        $userSearchService = $container->get(UserSearch::class);

        return new UserSearchHandler($userSearchService);
    }
}
