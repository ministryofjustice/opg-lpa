<?php

namespace ApplicationTest\Command;

use Application\Command\GenerateStatsCommand;
use Application\Model\Service\System\Stats as StatsService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateStatsCommandTest extends MockeryTestCase
{
    public function testExecuteSuccess()
    {
        $statsService = Mockery::mock(StatsService::class);
        $statsService->shouldReceive('generate')->andReturnNull();

        $input = Mockery::mock(InputInterface::class);
        $output = Mockery::mock(OutputInterface::class);

        $command = new GenerateStatsCommand();
        $command->setStatsService($statsService);

        $result = $command->execute($input, $output);

        $this->assertNull($result);
    }
}
