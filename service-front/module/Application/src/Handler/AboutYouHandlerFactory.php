<?php

declare(strict_types=1);

namespace Application\Handler;

use Application\Model\Service\User\Details;
use Laminas\Form\FormElementManager;
use Psr\Container\ContainerInterface;
use Twig\Environment as TwigEnvironment;

class AboutYouHandlerFactory
{
    public function __invoke(ContainerInterface $container): AboutYouHandler
    {
        return new AboutYouHandler(
            $container->get(FormElementManager::class),
            $container->get(TwigEnvironment::class),
            $container->get(Details::class),
        );
    }
}
