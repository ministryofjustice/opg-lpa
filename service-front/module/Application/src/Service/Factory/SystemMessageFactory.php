<?php

declare(strict_types=1);

namespace Application\Service;

use Psr\Container\ContainerInterface;

final class SystemMessageFactory
{
    public function __invoke(ContainerInterface $container): SystemMessage
    {
        return new SystemMessage(
            $container->get('DynamoDbSystemMessageCache'),
        );
    }
}
