<?php

namespace Application\Command;

use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Laminas\ServiceManager\ServiceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command is triggered daily from a cron job.
 *
 * Invoke from inside the api-app container, /app directory, with:
 *
 *   ./vendor/bin/laminas service-api:account-cleanup [--dry-run]
 *
 * With --dry-run nothing is deleted, flagged or emailed. Instead, the actions that would
 * have been taken are logged instead.
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

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Log what would be deleted, flagged or emailed vs doing any of it'
        );
    }

    /**
     * Required method implementation
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->accountCleanupService->cleanup((bool) $input->getOption('dry-run'));
    }
}
