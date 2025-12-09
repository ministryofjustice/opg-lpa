<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\FeedbackThanksHandler;
use Psr\Container\ContainerInterface;
use Twig\Environment as TwigEnvironment;

class FeedbackThanksHandlerFactory
{
    public function __invoke(ContainerInterface $container): FeedbackThanksHandler
    {
        return new FeedbackThanksHandler(
            $container->get(TwigEnvironment::class)
        );
    }
}
