<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\AboutYouHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class AboutYouHandlerFactory
{
    public function __invoke(ContainerInterface $container): AboutYouHandler
    {
        return new AboutYouHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(AuthenticationService::class),
            $container->get(UserService::class),
            $container->get(SessionUtility::class),
            $container->get(FlashMessenger::class),
        );
    }
}
