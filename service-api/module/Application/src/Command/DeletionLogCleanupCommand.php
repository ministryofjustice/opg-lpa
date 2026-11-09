<?php

declare(strict_types=1);

namespace Application\Command;

use Application\Model\Service\DeletionLogCleanupService;
use Laminas\ServiceManager\ServiceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Deletes expired entries from the deletion log and notifies the configured recipients.
 *
 * Invoke from inside the api-app container, /app directory, with:
 *
 *   ./vendor/bin/laminas service-api:deletion-log-cleanup
 */
class DeletionLogCleanupCommand extends Command
{
    private DeletionLogCleanupService $deletionLogCleanupService;

    public function __invoke(ServiceManager $sm): self
    {
        $this->setDeletionLogCleanupService($sm->get(DeletionLogCleanupService::class));
        return $this;
    }

    public function setDeletionLogCleanupService(DeletionLogCleanupService $deletionLogCleanupService): void
    {
        $this->deletionLogCleanupService = $deletionLogCleanupService;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->deletionLogCleanupService->cleanup();
    }
}
