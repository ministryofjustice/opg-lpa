<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\FeedbackThanksHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class FeedbackThanksHandlerFactory
{
    public function __invoke(ContainerInterface $container): FeedbackThanksHandler
    {
        return new FeedbackThanksHandler(
            $container->get(TemplateRendererInterface::class)
        );
    }
}
