<?php

namespace Application\Command;

use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Laminas\ServiceManager\ServiceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is triggered daily from a cron job.
 *
 * Invoke from inside the api-app container, /app directory, with:
 *
 *   ./vendor/bin/laminas service-api:account-cleanup
 *
 * Note that the AccountCleanupService instance requires a valid client ID,
 * otherwise it fails to instantiate.
 */
class AccountCleanupCommand extends Command
{
    /**
     * @var AccountCleanupService
     */
    private $accountCleanupService;

    /**
     * Factory method
     *
     * @param ServiceManager $sm
     * @return AccountCleanupCommand
     */
    public function __invoke(ServiceManager $sm)
    {
        $this->setAccountCleanupService($sm->get(AccountCleanupService::class));
        return $this;
    }

    public function setAccountCleanupService(AccountCleanupService $accountCleanupService): void
    {
        $this->accountCleanupService = $accountCleanupService;
    }

    /**
     * Required method implementation
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->accountCleanupService->cleanup();
    }
}
