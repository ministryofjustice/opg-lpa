<?php

namespace ApplicationTest\Command;

use Application\Command\AccountCleanupCommand;
use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AccountCleanupCommandTest extends MockeryTestCase
{
    public static function dryRunProvider(): array
    {
        return [
            'default' => [false],
            'dry run' => [true],
        ];
    }

    #[DataProvider('dryRunProvider')]
    public function testAccountCleanupCommandSuccess(bool $dryRun)
    {
        $cleanUpService = Mockery::mock(AccountCleanupService::class);
        $cleanUpService->shouldReceive('cleanup')->with($dryRun)->once()->andReturn(0);

        $input = Mockery::mock(InputInterface::class);
        $input->shouldReceive('getOption')->with('dry-run')->andReturn($dryRun);
        $output = Mockery::mock(OutputInterface::class);

        $command = new AccountCleanupCommand();
        $command->setAccountCleanupService($cleanUpService);

        $result = $command->execute($input, $output);

        $this->assertEquals(0, $result);
    }
}
