<?php
/**
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @copyright Copyright (c) 2014 Matthew Weier O'Phinney
 */

namespace PhlyMongo;

use MongoDB\Driver\Manager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MongoConnectionFactory implements FactoryInterface
{
    /**
     * Server connection string
     *
     * @var string
     */
    protected $server = 'mongodb://localhost:27017';

    /**
     * Connection options
     *
     * @var array
     */
    protected $options = [
        'connect' => true
    ];

    /**
     * MongoConnectionFactory constructor.
     * @param null $server
     * @param array|null $options
     */
    public function __construct($server = null, array $options = null)
    {
        if (null !== $server) {
            $this->server = $server;
        }
        if (null !== $options) {
            $this->options = $options;
        }
    }

    public function createService(ServiceLocatorInterface $services)
    {
        return new Manager($this->server, $this->options);
    }
}
