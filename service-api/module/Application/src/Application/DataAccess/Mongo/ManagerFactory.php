<?php

namespace Application\DataAccess\Mongo;

use MongoDB\Driver\Manager;
use Zend\ServiceManager\ServiceLocatorInterface;

class ManagerFactory implements IManagerFactory
{
    /**
     * @var string
     */
    private $uri;
    /**
     * @var array
     */
    private $options;
    /**
     * @var array
     */
    private $driverOptions;

    public function __construct($uri, $options = [], $driverOptions = [])
    {
        $this->uri = $uri;
        $this->options = $options;
        $this->driverOptions = $driverOptions;
    }

    /**
     * Create MongoDB Manager
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Manager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $manager = new Manager($this->uri, $this->options, $this->driverOptions);
        return $manager;
    }
}