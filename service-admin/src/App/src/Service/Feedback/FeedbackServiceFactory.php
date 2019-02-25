<?php

namespace App\Service\Feedback;

use App\Service\ApiClient\Client as ApiClient;
use Interop\Container\ContainerInterface;

/**
 * Class FeedbackServiceFactory
 * @package App\Service\Feedback
 */
class FeedbackServiceFactory
{
    /**
     * @param ContainerInterface $container
     * @return FeedbackService
     */
    public function __invoke(ContainerInterface $container)
    {
        return new FeedbackService($container->get(ApiClient::class));
    }
}
