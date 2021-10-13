<?php

declare(strict_types=1);

namespace App\Handler;

use App\Service\Feedback\FeedbackService;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class FeedbackHandlerFactory
 * @package App\Handler
 */
class FeedbackHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return RequestHandlerInterface
     */
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        $feedbackService = $container->get(FeedbackService::class);

        return new FeedbackHandler($feedbackService);
    }
}
