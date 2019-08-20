<?php

namespace Application\ControllerFactory;

use Application\Controller\Console\AccountCleanupController;
use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class AccountCleanupControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return AccountCleanupController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var AccountCleanupService $accountCleanupService */
        $accountCleanupService = $container->get(AccountCleanupService::class);

        return new AccountCleanupController($accountCleanupService);
    }
}
