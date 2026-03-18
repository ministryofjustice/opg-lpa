<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\DeleteAccountConfirmHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\User\Details as UserService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class DeleteAccountConfirmHandlerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): DeleteAccountConfirmHandler
    {
        return new DeleteAccountConfirmHandler(
            $container->get(TemplateRendererInterface::class),
            $container->get(AuthenticationService::class),
            $container->get(UserService::class),
        );
    }
}
