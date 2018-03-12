<?php

namespace Application\ControllerFactory;

use Application\Controller\AbstractAuthenticatedController;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Initializer\InitializerInterface;

class UserAwareInitializer implements InitializerInterface
{
    /**
     * Initialize the given instance
     *
     * @param  ContainerInterface $container
     * @param  object $instance
     * @return void
     */
    public function __invoke(ContainerInterface $container, $instance)
    {
        if ($instance instanceof AbstractAuthenticatedController) {
            $auth = $container->get('AuthenticationService');

            if ($auth->hasIdentity()) {
                $instance->setUser($auth->getIdentity());
            }
        }
    }
}
