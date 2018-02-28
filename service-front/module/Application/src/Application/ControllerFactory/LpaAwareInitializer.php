<?php

namespace Application\ControllerFactory;

use Application\Controller\AbstractLpaController;
use Interop\Container\ContainerInterface;
use Opg\Lpa\DataModel\Lpa\Lpa;
use RuntimeException;
use Zend\ServiceManager\Initializer\InitializerInterface;

class LpaAwareInitializer implements InitializerInterface
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
        if ($instance instanceof AbstractLpaController) {
            $auth = $container->get('AuthenticationService');

            // We don't do anything here without a user.
            if (!$auth->hasIdentity()) {
                return;
            }

            // Find the LPA ID form the URL...
            $lpaId = $container->get('Application')->getMvcEvent()->getRouteMatch()->getParam('lpa-id');

            if (!is_numeric($lpaId)) {
                throw new RuntimeException('Invalid LPA ID passed');
            }

            // Load the LPA...
            $lpa = $container->get('LpaApplicationService')->getApplication((int) $lpaId);

            // If it's an object, assume it's an LPA (if it's not it'll be picked up later)
            if ($lpa instanceof Lpa) {
                /**
                 * Conduct some paranoia checks. Check the owner of the LPA matches the current user.
                 * The API client would not have been allowed access to it if there was a miss-match, but
                 * there's no reason not to check again here.
                 */

                if ($auth->getIdentity()->id() !== $lpa->user) {
                    throw new RuntimeException('Invalid LPA ID');
                }

                $instance->setLpa($lpa);
            }
        }
    }
}
