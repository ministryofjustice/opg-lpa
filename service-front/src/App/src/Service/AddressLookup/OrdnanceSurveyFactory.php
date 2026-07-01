<?php

declare(strict_types=1);

namespace App\Service\AddressLookup;

use Http\Adapter\Guzzle7\Client as GuzzleClient;
use Psr\Container\ContainerInterface;

class OrdnanceSurveyFactory
{
    public function __invoke(ContainerInterface $container): OrdnanceSurvey
    {
        $config = $container->get('config');

        return new OrdnanceSurvey(
            new GuzzleClient(),
            $config['address']['ordnancesurvey']['key'] ?? '',
            $config['address']['ordnancesurvey']['endpoint'] ?? '',
        );
    }
}
