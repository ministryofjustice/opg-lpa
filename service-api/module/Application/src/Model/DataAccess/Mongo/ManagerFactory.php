<?php

namespace Application\Model\DataAccess\Mongo;

use Interop\Container\ContainerInterface;
use MongoDB\Driver\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;

class ManagerFactory implements FactoryInterface
{
    /**
     * @var string
     */
    private $configKey;

    /**
     * @param string $configKey
     */
    public function __construct($configKey = 'default')
    {
        $this->configKey = $configKey;
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Manager
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['db']['mongo'][$this->configKey];

        // Split the array out into comma separated values.
        $uri = 'mongodb://' . implode(',', $config['hosts']) . '/' . $config['options']['db'];

        $options = $config['options'];

        if (array_key_exists('socketTimeoutMS', $options)) {
            if (is_int($options['socketTimeoutMS'])) {
                // This connection option only works on the url itself
                $uri .= '?socketTimeoutMS=' . $options['socketTimeoutMS'];
            }
            unset($options['socketTimeoutMS']);
        }

        return new Manager($uri, $options, $config['driverOptions']);
    }
}
