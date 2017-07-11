<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Model\Rest\Applications\Entity;
use Application\Model\Rest\Applications\Resource;
use Application\Model\Rest\Users\Entity as UserEntity;
use Mockery;
use Mockery\MockInterface;
use MongoCollection;
use Opg\Lpa\DataModel\User\User;
use Opg\Lpa\Logger\Logger;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Log\LoggerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfcRbac\Service\AuthorizationService;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var User
     */
    private $user = null;

    /**
     * @var Resource
     */
    private $resource = null;

    /**
     * @var MockInterface
     */
    private $lpaCollection = null;

    protected function setUp()
    {
        parent::setUp();

        $this->user = FixturesData::getUser();

        $authorizationServiceMock = Mockery::mock(AuthorizationService::class);
        $authorizationServiceMock
            ->shouldReceive('isGranted')
            ->with('authenticated')
            ->andReturn(true);
        $authorizationServiceMock
            ->shouldReceive('isGranted')
            ->with('isAuthorizedToManageUser', $this->user->get('id'))
            ->andReturn(true);

        $loggerMock = Mockery::mock(LoggerInterface::class);
        $loggerMock->shouldReceive('info');

        $serviceLocatorMock = Mockery::mock(ServiceLocatorInterface::class);
        $serviceLocatorMock->shouldReceive('get')->with('Logger')->andReturn($loggerMock);

        $this->lpaCollection = Mockery::mock(MongoCollection::class);
        $this->lpaCollection->shouldReceive('findOne')->andReturn(null);
        $serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-lpa')->andReturn($this->lpaCollection);

        $this->resource = new Resource();
        $this->resource->setServiceLocator($serviceLocatorMock);
        $this->resource->setAuthorizationService($authorizationServiceMock);
        $this->resource->setRouteUser(new UserEntity($this->user));
    }

    public function testCreateNullData()
    {
        $this->lpaCollection->shouldReceive('insert')->once();
        $this->resource->create(null);
        $this->lpaCollection->mockery_verify();
    }

    public function tearDown() {
        \Mockery::close();
    }
}