<?php

namespace App\Middleware\Authorization;

use Interop\Container\ContainerInterface;
use Zend\Expressive\Handler\NotFoundHandler;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Permissions\Rbac\Rbac;
use Exception;

/**
 * Class AuthorizationMiddlewareFactory
 * @package App\Middleware\Authorization
 */
class AuthorizationMiddlewareFactory
{
    /**
     * @param ContainerInterface $container
     * @return AuthorizationMiddleware
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        if (!isset($config['rbac']['roles'])) {
            throw new Exception('Rbac roles are not configured');
        }

        if (!isset($config['rbac']['permissions'])) {
            throw new Exception('Rbac permissions are not configured');
        }

        $rbac = new Rbac();
        $rbac->setCreateMissingRoles(true);

        //  Roles and parents
        foreach ($config['rbac']['roles'] as $role => $parents) {
            $rbac->addRole($role, $parents);
        }

        //  Permissions
        foreach ($config['rbac']['permissions'] as $role => $permissions) {
            foreach ($permissions as $perm) {
                $rbac->getRole($role)->addPermission($perm);
            }
        }

        $urlHelper = $container->get(UrlHelper::class);
        $notFoundHandler = $container->get(NotFoundHandler::class);

        return new AuthorizationMiddleware($urlHelper, $rbac, $notFoundHandler);
    }
}
