<?php

namespace Application\Command;

use Application\Model\Service\System\Stats as StatsService;
use Laminas\ServiceManager\ServiceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * laminas-cli command to generate stats.
 *
 * This command is triggered daily from a cron job.
 *
 * Invoke from inside the api-app container, /app directory, with:
 *
 *   ./vendor/bin/laminas service-api:generate-stats
 */
class GenerateStatsCommand extends Command
{
    /**
     * @var StatsService
     */
    private $statsService;

    /**
     * Factory method
     *
     * @param ServiceManager $sm
     * @return GenerateStatsCommand
     */
    public function __invoke(ServiceManager $sm)
    {
        $this->setStatsService($sm->get(StatsService::class));
        return $this;
    }

    public function setStatsService(StatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    /**
     * Required method implementation
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $success = $this->statsService->generate();
        if ($success) {
            return 0;
        }
        return 1;
    }
}
