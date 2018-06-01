<?php

namespace Auth;

use Auth\Model\DataAccess\Mongo;
use Auth\Model\DataAccess\Mongo\Factory\CollectionFactory;
use Auth\Model\DataAccess\Mongo\Factory\DatabaseFactory;
use Auth\Model\DataAccess\Mongo\Factory\ManagerFactory;
use Aws\Sns\SnsClient;
use GuzzleHttp\Client as GuzzleClient;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getServiceConfig()
    {
        return [
            'factories' => [
                'UserDataSource' => function ($services) {
                    return new Mongo\UserCollection($services->get(CollectionFactory::class . '-user'));
                },
                'LogDataSource' => function ($services) {
                    return new Mongo\LogCollection($services->get(CollectionFactory::class . '-log'));
                },

                'SnsClient' => function (ServiceLocatorInterface $sm) {
                    $config = $sm->get('Config')['log']['sns'];

                    return new SnsClient($config['client']);
                },

                'GuzzleClient' => function (ServiceLocatorInterface $sm) {
                    return new GuzzleClient();
                },

                // Create an instance of the MongoClient...
                ManagerFactory::class => ManagerFactory::class,

                // Connect the above MongoClient to a DB...
                DatabaseFactory::class => DatabaseFactory::class,

                // Access collections within the above DB...
                CollectionFactory::class . '-user' => new CollectionFactory('user'),
                CollectionFactory::class . '-log' => new CollectionFactory('log'),

            ], // factories

        ];
    }

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
