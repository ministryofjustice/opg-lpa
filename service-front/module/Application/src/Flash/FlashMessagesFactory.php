<?php

declare(strict_types=1);

namespace Application\Flash;

use Mezzio\Flash\FlashMessages;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Psr\Container\ContainerInterface;

class FlashMessagesFactory
{
    public function __invoke(ContainerInterface $container): FlashMessagesInterface
    {
        $application = $container->get('Application');
        $request = $application->getMvcEvent()->getRequest();
        $session = $request->getAttribute(SessionInterface::class);

        return FlashMessages::createFromSession($session);
    }
}
