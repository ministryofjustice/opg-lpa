<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\ForgotPasswordHandler;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ForgotPasswordHandlerFactory
{
    public function __invoke(ContainerInterface $container): ForgotPasswordHandler
    {
        return new ForgotPasswordHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(UserService::class),
        );
    }
}
