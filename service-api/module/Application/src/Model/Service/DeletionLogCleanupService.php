<?php

declare(strict_types=1);

namespace Application\Model\Service;

use Alphagov\Notifications\Client as NotifyClient;
use Application\Model\DataAccess\Postgres\LogData;
use Psr\Log\LoggerInterface;

class DeletionLogCleanupService
{
    public const string NOTIFICATION_TEMPLATE_ID = '5f250b39-8c60-4619-80e2-0a59eef16089';

    public function __construct(
        private LogData $deletionLogRepository,
        private NotifyClient $notifyClient,
        private array $config,
        private LoggerInterface $logger,
    ) {
    }

    public function cleanup(): int
    {
        $this->logger->info('Deletion log cleanup started');

        $count = $this->deletionLogRepository->deleteExpiredDeletionLogs();
        $recipients = $this->config['admin']['account_cleanup_notification_recipients'] ?? [];
        $exitCode = 0;

        if (empty($recipients)) {
            $this->logger->warning('No recipients configured for deletion log cleanup notification', [
                'expired_deletion_logs_count' => $count,
            ]);
        }

        foreach ($recipients as $recipient) {
            try {
                $this->notifyClient->sendEmail($recipient, self::NOTIFICATION_TEMPLATE_ID, [
                    'stack' => $this->config['stack']['name'] ?? 'unknown',
                    'expiredDeletionLogsCount' => $count,
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Failed to send deletion log cleanup notification email', [
                    'exception' => $e,
                ]);
                $exitCode = 1;
            }
        }

        $this->logger->info('Deletion log cleanup completed', [
            'expired_deletion_logs_count' => $count,
            'recipients_count'            => count($recipients),
            'exit_code'                   => $exitCode,
        ]);

        return $exitCode;
    }
}
