<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Service\AccordionService;
use Psr\Container\ContainerInterface;

class AccordionServiceFactory
{
    public function __invoke(ContainerInterface $container): AccordionService
    {
        return new AccordionService();
    }
}
