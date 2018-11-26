<?php

namespace App\Service\UserSearch;

use App\Service\ApiClient\Client as ApiClient;
use Interop\Container\ContainerInterface;

/**
 * Class UserSearchFactory
 * @package App\Service\UserSearch
 */
class UserSearchFactory
{
    /**
     * @param ContainerInterface $container
     * @return UserSearch
     */
    public function __invoke(ContainerInterface $container)
    {
        return new UserSearch($container->get(ApiClient::class));
    }
}
