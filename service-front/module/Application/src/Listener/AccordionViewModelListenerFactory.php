<?php

declare(strict_types=1);

namespace Application\Listener;

use Application\Service\AccordionService;
use Psr\Container\ContainerInterface;

final class AccordionViewModelListenerFactory
{
    public function __invoke(ContainerInterface $container): AccordionViewModelListener
    {
        return new AccordionViewModelListener(
            $container->get(AccordionService::class),
            $container->get('PersistentSessionDetails')
        );
    }
}
