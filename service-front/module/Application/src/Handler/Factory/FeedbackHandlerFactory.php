<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\FeedbackHandler;
use Application\Model\Service\Feedback\Feedback;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Form\FormElementManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Twig\Environment as TwigEnvironment;

class FeedbackHandlerFactory
{
    public function __invoke(ContainerInterface $container): FeedbackHandler
    {
        return new FeedbackHandler(
            $container->get(TwigEnvironment::class),
            $container->get(FormElementManager::class),
            $container->get(Feedback::class),
            $container->get(SessionUtility::class),
            $container->get(LoggerInterface::class),
        );
    }
}
