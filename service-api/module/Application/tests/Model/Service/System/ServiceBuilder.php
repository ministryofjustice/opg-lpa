<?php

namespace ApplicationTest\Model\Service\System;

use Application\Model\Service\System\Stats;
use ApplicationTest\Model\Service\AbstractServiceBuilder;

class ServiceBuilder extends AbstractServiceBuilder
{
    /**
     * @return Stats
     */
    public function build()
    {
        /** @var Stats $stats */
        $stats = parent::buildMocks(Stats::class);
        return $stats;
    }
}
