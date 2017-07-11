<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Model\Rest\Applications\Entity;
use Application\Model\Rest\Applications\Resource;
use Application\Model\Rest\Users\Entity as UserEntity;
use Mockery;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\Logger\Logger;
use Zend\Log\LoggerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfcRbac\Service\AuthorizationService;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    private $userId = 3;

    /**
     * @var User
     */
    private $user = null;

    /**
     * @var Resource
     */
    private $resource = null;

    protected function setUp()
    {
        parent::setUp();

        $user = new User();
        $user->set('id', $this->userId);

        $loggerMock = Mockery::mock(LoggerInterface::class);
        $loggerMock->shouldReceive('info');
        $authorizationServiceMock = Mockery::mock(AuthorizationService::class);
        $authorizationServiceMock->shouldReceive('isGranted')->with('authenticated')->andReturn(true);
        $authorizationServiceMock->shouldReceive('isGranted')->with('isAuthorizedToManageUser', $this->userId)->andReturn(true);
        $loggerMock->shouldReceive('info');
        $serviceLocatorMock = Mockery::mock(ServiceLocatorInterface::class);
        $serviceLocatorMock->shouldReceive('get')->with('Logger')->andReturn($loggerMock);
        $this->resource = new Resource();
        $this->resource->setServiceLocator($serviceLocatorMock);
        $this->resource->setAuthorizationService($authorizationServiceMock);
        $this->resource->setRouteUser(new UserEntity($user));
    }

    public function testCreateNullData()
    {
        $this->resource->create(null);
    }
}