<?php

declare(strict_types=1);

namespace Application\Handler\Factory;

use Application\Handler\PostcodeHandler;
use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class PostcodeHandlerFactory
{
    public function __invoke(ContainerInterface $container): PostcodeHandler
    {
        return new PostcodeHandler(
            $container->get(OrdnanceSurvey::class),
            $container->get(LoggerInterface::class),
        );
    }
}
