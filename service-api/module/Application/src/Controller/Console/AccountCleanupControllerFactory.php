<?php

namespace Application\Controller\Console;

use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Application\Model\Service\System\DynamoCronLock;
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
        /** @var DynamoCronLock $dynamoCronLock */
        $dynamoCronLock = $container->get('DynamoCronLock');
        /** @var AccountCleanupService $accountCleanupService */
        $accountCleanupService = $container->get(AccountCleanupService::class);

        return new AccountCleanupController($dynamoCronLock, $accountCleanupService);
    }
}
