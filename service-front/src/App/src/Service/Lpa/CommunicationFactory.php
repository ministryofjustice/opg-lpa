<?php

declare(strict_types=1);

namespace App\Service\Lpa;

use App\Model\UserDetailsHolder;
use App\Service\Mail\Transport\MailTransportInterface;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

class CommunicationFactory
{
    public function __invoke(ContainerInterface $container): Communication
    {
        $service = new Communication($container->get(MailTransportInterface::class));
        $service->setUrlHelper($container->get(UrlHelper::class));
        $service->setUserDetailsHolder($container->get(UserDetailsHolder::class));

        return $service;
    }
}
