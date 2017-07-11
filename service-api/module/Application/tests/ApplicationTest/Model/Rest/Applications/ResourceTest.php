<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Library\DateTime;
use Application\Model\Rest\Applications\Entity;
use Application\Model\Rest\Applications\Resource;
use Application\Model\Rest\Users\Entity as UserEntity;
use Mockery;
use Mockery\Mock;
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
     * @var Mock
     */
    private $lpaCollection = null;

    protected function setUp()
    {
        parent::setUp();

        $this->user = FixturesData::getUser();

        $authorizationServiceMock = Mockery::mock(AuthorizationService::class);
        $authorizationServiceMock->shouldReceive('isGranted')->andReturn(true);

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

    public function testGetType()
    {
        $this->assertEquals('collections', $this->resource->getType());
    }

    public function testCreateNullData()
    {
        $this->lpaCollection->shouldReceive('insert')->once();

        /* @var Entity */
        $createdLpa = $this->resource->create(null);

        $this->assertNotNull($createdLpa);
        $this->assertGreaterThan(0, $createdLpa->lpaId());
        $this->lpaCollection->mockery_verify();
    }

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $user = new User();
        $user->set('id', 3);
        $this->resource->setRouteUser(new UserEntity($user));

        //So we expect an exception and for no document to be inserted
        $this->setExpectedException(\RuntimeException::class, 'A malformed LPA object was created');
        $this->lpaCollection->shouldNotReceive('insert');

        $this->resource->create(null);
        $this->lpaCollection->mockery_verify();
    }

    public function testCreateFullLpa()
    {
        $lpa = FixturesData::getHwLpa();

        $this->lpaCollection->shouldReceive('insert')->once();

        /* @var Entity */
        $createdLpa = $this->resource->create($lpa->toArray());

        $this->assertNotNull($createdLpa);
        //Id should be generated
        $this->assertNotEquals($lpa->get('id'), $createdLpa->lpaId());
        $this->assertGreaterThan(0, $createdLpa->lpaId());
        //User should be reassigned to logged in user
        $this->assertEquals($this->user->get('id'), $createdLpa->userId());

        $this->lpaCollection->mockery_verify();
    }

    public function testCreateFilterIncomingData()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->set('lockedAt', new DateTime());
        $lpa->set('locked', true);

        $this->lpaCollection->shouldReceive('insert');

        /* @var Entity */
        $createdLpa = $this->resource->create($lpa->toArray());

        //The following properties should be maintained
        $this->assertEquals($lpa->get('document'), $createdLpa->getLpa()->get('document'));
        $this->assertEquals($lpa->get('metadata'), $createdLpa->getLpa()->get('metadata'));
        $this->assertEquals($lpa->get('payment'), $createdLpa->getLpa()->get('payment'));
        $this->assertEquals($lpa->get('repeatCaseNumber'), $createdLpa->getLpa()->get('repeatCaseNumber'));
        //All others should be ignored
        $this->assertNotEquals($lpa->get('startedAt'), $createdLpa->getLpa()->get('startedAt'));
        $this->assertNotEquals($lpa->get('createdAt'), $createdLpa->getLpa()->get('updatedAt'));
        $this->assertNotEquals($lpa->get('startedAt'), $createdLpa->getLpa()->get('startedAt'));
        $this->assertNotEquals($lpa->get('completedAt'), $createdLpa->getLpa()->get('completedAt'));
        $this->assertNotEquals($lpa->get('lockedAt'), $createdLpa->getLpa()->get('lockedAt'));
        $this->assertNotEquals($lpa->get('user'), $createdLpa->getLpa()->get('user'));
        $this->assertNotEquals($lpa->get('whoAreYouAnswered'), $createdLpa->getLpa()->get('whoAreYouAnswered'));
        $this->assertNotEquals($lpa->get('locked'), $createdLpa->getLpa()->get('locked'));
        $this->assertNotEquals($lpa->get('seed'), $createdLpa->getLpa()->get('seed'));

        $this->lpaCollection->mockery_verify();
    }

    public function tearDown()
    {
        Mockery::close();
    }
}