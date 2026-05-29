<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\FeedbackHandler;
use Application\Model\Service\Date\IDateService;
use Application\Model\Service\Feedback\Feedback;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class FeedbackHandlerFactory
{
    public function __invoke(ContainerInterface $container): FeedbackHandler
    {
        return new FeedbackHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(Feedback::class),
            $container->get(LoggerInterface::class),
            $container->get(IDateService::class),
        );
    }
}
