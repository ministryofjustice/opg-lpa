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

    public function __construct($uri, $options = [])
    {
        $this->uri = $uri;
        $this->options = $options;
    }

    /**
     * Create MongoDB Manager
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Manager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $manager = new Manager($this->uri, $this->options);
        return $manager;
    }
}