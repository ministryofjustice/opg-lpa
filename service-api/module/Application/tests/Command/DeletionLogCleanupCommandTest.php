<?php

namespace ApplicationTest\Command;

use Application\Command\DeletionLogCleanupCommand;
use Application\Model\Service\DeletionLogCleanupService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeletionLogCleanupCommandTest extends MockeryTestCase
{
    public static function exitCodeProvider(): array
    {
        return [
            'success' => [0],
            'failure' => [1],
        ];
    }

    #[DataProvider('exitCodeProvider')]
    public function testExecuteReturnsServiceExitCode(int $exitCode)
    {
        $cleanUpService = Mockery::mock(DeletionLogCleanupService::class);
        $cleanUpService->shouldReceive('cleanup')->once()->andReturn($exitCode);

        $input = Mockery::mock(InputInterface::class);
        $output = Mockery::mock(OutputInterface::class);

        $command = new DeletionLogCleanupCommand();
        $command->setDeletionLogCleanupService($cleanUpService);

        $this->assertEquals($exitCode, $command->execute($input, $output));
    }
}
