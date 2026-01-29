<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\ResetPasswordHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\Session\SessionManager;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ResetPasswordHandlerFactory
{
    public function __invoke(ContainerInterface $container): ResetPasswordHandler
    {
        return new ResetPasswordHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(UserService::class),
            $container->get(UrlHelper::class),
            $container->get(AuthenticationService::class),
            $container->get(SessionManager::class),
            $container->get(FlashMessenger::class),
        );
    }
}
