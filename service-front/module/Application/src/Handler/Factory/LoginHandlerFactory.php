<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\LoginHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class LoginHandlerFactory
{
    public function __invoke(ContainerInterface $container): LoginHandler
    {
        return new LoginHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(AuthenticationService::class),
            $container->get(SessionManagerSupport::class),
            $container->get(SessionUtility::class),
            $container->get(LpaApplicationService::class),
            $container->get(FlashMessenger::class)
        );
    }
}
