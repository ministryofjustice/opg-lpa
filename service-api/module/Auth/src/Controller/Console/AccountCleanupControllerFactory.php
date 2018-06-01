<?php

namespace Auth\Controller\Console;

use Auth\Model\Service\AccountCleanupService;
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
        /** @var AccountCleanupService $accountCleanupService */
        $accountCleanupService = $container->get(AccountCleanupService::class);
        /** @var DynamoCronLock $dynamoCronLock */
        $dynamoCronLock = $container->get('DynamoCronLock');

        return new AccountCleanupController($accountCleanupService, $dynamoCronLock, $container->get('config'));
    }
}
