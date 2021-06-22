<?php

namespace ApplicationTest\Command;

use Application\Command\AccountCleanupCommand;
use Application\Model\Service\AccountCleanup\Service as AccountCleanupService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AccountCleanupCommandTest extends MockeryTestCase
{
    public function testAccountCleanupCommandSuccess()
    {
        $cleanUpService = Mockery::mock(AccountCleanupService::class);
        $cleanUpService->shouldReceive('cleanup')->andReturnNull();

        $input = Mockery::mock(InputInterface::class);
        $output = Mockery::mock(OutputInterface::class);

        $command = new AccountCleanupCommand();
        $command->setAccountCleanupService($cleanUpService);

        $result = $command->execute($input, $output);

        $this->assertNull($result);
    }
}
