<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use App\Handler\ResendActivationEmailHandler;
use Application\Model\Service\User\Details as UserService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class ResendActivationEmailHandlerFactory
{
    public function __invoke(ContainerInterface $container): ResendActivationEmailHandler
    {
        return new ResendActivationEmailHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(FormElementManager::class),
            $container->get(UserService::class),
        );
    }
}
