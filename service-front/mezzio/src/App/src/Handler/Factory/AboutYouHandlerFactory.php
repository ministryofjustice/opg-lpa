<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\AboutYouHandler;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class AboutYouHandlerFactory
{
    public function __invoke(ContainerInterface $container): AboutYouHandler
    {
        return new AboutYouHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(UserService::class),
        );
    }
}
