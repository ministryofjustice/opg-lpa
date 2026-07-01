<?php

declare(strict_types=1);

namespace App\Service\Feedback;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\Mail\Transport\MailTransportInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class FeedbackServiceFactory
{
    public function __invoke(ContainerInterface $container): FeedbackService
    {
        $config = $container->get('config');

        return new FeedbackService(
            $container->get(ApiClient::class),
            $container->get(LoggerInterface::class),
            $container->get(MailTransportInterface::class),
            $config['email']['sendFeedbackEmailTo'] ?? (getenv('OPG_LPA_FRONT_EMAIL_SENDTO') ?: ''),
        );
    }
}
