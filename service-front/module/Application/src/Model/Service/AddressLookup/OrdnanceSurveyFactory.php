<?php
namespace Application\Model\Service\AddressLookup;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OrdnanceSurveyFactory implements FactoryInterface
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        if (!isset($config['address']['ordnancesurvey']['key'])) {
            throw new \UnexpectedValueException('Ordnance Survey API key not configured');
        }

        if (!isset($config['address']['ordnancesurvey']['endpoint'])) {
            throw new \UnexpectedValueException('Ordnance Survey API endpoint not configured');
        }

        return new OrdnanceSurvey(
            $container->get('HttpClient'),
            $config['address']['ordnancesurvey']['key'],
            $config['address']['ordnancesurvey']['endpoint']
        );
    }

}
