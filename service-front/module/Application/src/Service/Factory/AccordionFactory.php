<?php

declare(strict_types=1);

namespace Application\Service;

use Psr\Container\ContainerInterface;

final class AccordionFactory
{
    public function __invoke(ContainerInterface $container): AccordionService
    {
        /** @var array $config */
        $config = $container->get('config');

        $bars = $config['accordion']['bars'] ?? [];

        return new AccordionService($bars);
    }
}
