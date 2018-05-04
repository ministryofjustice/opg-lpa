<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Application\Model\Service\DataAccess\Mongo\Factory\CollectionFactory;
use Application\Model\Service\DataAccess\Mongo\Factory\DatabaseFactory;
use Application\Model\Service\DataAccess\Mongo\Factory\ManagerFactory;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Sns\SnsClient;
use GuzzleHttp\Client as GuzzleClient;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

use Application\Model\Service\DataAccess\Mongo;

use Application\Model\Service\System\DynamoCronLock;

class Module
{
    const VERSION = '3.0.3-dev';

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getServiceConfig() {
        return [
            'invokables' => [],
            'factories' => [

                'UserDataSource' => function ($services) {

                    return new Mongo\UserCollection( $services->get(CollectionFactory::class . '-user') );

                },

                'LogDataSource' => function ($services) {

                    return new Mongo\LogCollection( $services->get(CollectionFactory::class . '-log') );

                },

                //---

                'DynamoCronLock' => function ( ServiceLocatorInterface $sm ) {

                    $config = $sm->get('config')['cron']['lock']['dynamodb'];

                    return new DynamoCronLock(
                        new DynamoDbClient($config['client']),
                        $config['settings']['table_name'],
                        $sm->get('config')['stack']['name']
                    );

                },

                //---

                'SnsClient' => function ( ServiceLocatorInterface $sm ) {

                    $config = $sm->get('Config')['log']['sns'];

                    return new SnsClient( $config['client'] );

                },

                //---

                'GuzzleClient' => function ( ServiceLocatorInterface $sm ) {

                    return new GuzzleClient();

                },

                //---

                // Create an instance of the MongoClient...
                ManagerFactory::class => ManagerFactory::class,

                // Connect the above MongoClient to a DB...
                DatabaseFactory::class => DatabaseFactory::class,

                // Access collections within the above DB...
                CollectionFactory::class . '-user' => new CollectionFactory('user'),
                CollectionFactory::class . '-log' => new CollectionFactory('log'),

            ], // factories

        ];
    } // function

    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
