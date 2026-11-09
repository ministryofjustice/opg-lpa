<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service;

use Alphagov\Notifications\Client as NotifyClient;
use Application\Model\DataAccess\Postgres\LogData;
use Application\Model\Service\DeletionLogCleanupService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;

class DeletionLogCleanupServiceTest extends MockeryTestCase
{
    public function testCleanup(): void
    {
        $logData = Mockery::mock(LogData::class);
        $logData->shouldReceive('deleteExpiredDeletionLogs')
            ->once()
            ->andReturn(5);

        $notifyClient = Mockery::mock(NotifyClient::class);
        $notifyClient->shouldReceive('sendEmail')
            ->with('a@example.com', DeletionLogCleanupService::NOTIFICATION_TEMPLATE_ID, [
                'stack'                    => 'test',
                'expiredDeletionLogsCount' => 5,
            ])
            ->once();
        $notifyClient->shouldReceive('sendEmail')
            ->with('b@example.com', DeletionLogCleanupService::NOTIFICATION_TEMPLATE_ID, [
                'stack'                    => 'test',
                'expiredDeletionLogsCount' => 5,
            ])
            ->once();

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $config = [
            'admin' => ['account_cleanup_notification_recipients' => ['a@example.com', 'b@example.com']],
            'stack' => ['name' => 'test']
        ];
        $sut = new DeletionLogCleanupService($logData, $notifyClient, $config, $logger);

        $exitCode = $sut->cleanup();
        self::assertEquals(0, $exitCode);
    }

    public function testCleanupWithNoRecipients(): void
    {
        $logData = Mockery::mock(LogData::class);
        $logData->shouldReceive('deleteExpiredDeletionLogs')
            ->once()
            ->andReturn(3);

        $notifyClient = Mockery::mock(NotifyClient::class);
        $notifyClient->shouldNotReceive('sendEmail');

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $sut = new DeletionLogCleanupService($logData, $notifyClient, [], $logger);

        self::assertEquals(0, $sut->cleanup());
    }

    public function testCleanupDefaultsStackNameToUnknown(): void
    {
        $logData = Mockery::mock(LogData::class);
        $logData->shouldReceive('deleteExpiredDeletionLogs')
            ->once()
            ->andReturn(0);

        $notifyClient = Mockery::mock(NotifyClient::class);
        $notifyClient->shouldReceive('sendEmail')
            ->with('a@example.com', DeletionLogCleanupService::NOTIFICATION_TEMPLATE_ID, [
                'stack'                    => 'unknown',
                'expiredDeletionLogsCount' => 0,
            ])
            ->once();

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldNotReceive('error');

        $config = [
            'admin' => ['account_cleanup_notification_recipients' => ['a@example.com']],
        ];
        $sut = new DeletionLogCleanupService($logData, $notifyClient, $config, $logger);

        self::assertEquals(0, $sut->cleanup());
    }

    public function testCleanupReturnsErrorWhenEmailFails(): void
    {
        $logData = Mockery::mock(LogData::class);
        $logData->shouldReceive('deleteExpiredDeletionLogs')
            ->once()
            ->andReturn(5);

        $exception = new \Exception('notify down');

        $notifyClient = Mockery::mock(NotifyClient::class);
        $notifyClient->shouldReceive('sendEmail')
            ->with('a@example.com', DeletionLogCleanupService::NOTIFICATION_TEMPLATE_ID, Mockery::any())
            ->once()
            ->andThrow($exception);
        $notifyClient->shouldReceive('sendEmail')
            ->with('b@example.com', DeletionLogCleanupService::NOTIFICATION_TEMPLATE_ID, Mockery::any())
            ->once();

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('error')
            ->with('Failed to send deletion log cleanup notification email', ['exception' => $exception])
            ->once();

        $config = [
            'admin' => ['account_cleanup_notification_recipients' => ['a@example.com', 'b@example.com']],
            'stack' => ['name' => 'test'],
        ];
        $sut = new DeletionLogCleanupService($logData, $notifyClient, $config, $logger);

        self::assertEquals(1, $sut->cleanup());
    }
}
