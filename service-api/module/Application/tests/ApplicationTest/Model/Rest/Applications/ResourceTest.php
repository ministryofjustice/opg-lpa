<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\DateTime;
use Application\Model\Rest\Applications\Entity;
use Application\Model\Rest\Applications\Resource;
use Application\Model\Rest\Users\Entity as UserEntity;
use Mockery;
use Mockery\Mock;
use MongoCollection;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Log\LoggerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfcRbac\Service\AuthorizationService;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Lpa
     */
    private $pfLpa;
    private $pfLpaId;

    /**
     * @var Lpa
     */
    private $hwLpa;
    private $hwLpaId;

    /**
     * @var User
     */
    private $user = null;
    private $userId;

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

        $this->pfLpa = FixturesData::getPfLpa();
        $this->pfLpaId = $this->pfLpa->id;

        $this->hwLpa = FixturesData::getHwLpa();
        $this->hwLpaId = $this->hwLpa->id;

        $this->user = FixturesData::getUser();
        $this->userId = $this->user->id;

        $authorizationServiceMock = Mockery::mock(AuthorizationService::class);
        $authorizationServiceMock->shouldReceive('isGranted')->andReturn(true);

        $loggerMock = Mockery::mock(LoggerInterface::class);
        $loggerMock->shouldReceive('info');

        $serviceLocatorMock = Mockery::mock(ServiceLocatorInterface::class);
        $serviceLocatorMock->shouldReceive('get')->with('Logger')->andReturn($loggerMock);

        $this->lpaCollection = Mockery::mock(MongoCollection::class);
        $this->lpaCollection->shouldReceive('findOne')->with(['_id'=>(int)$this->pfLpaId, 'user'=>$this->userId])->andReturn($this->pfLpa->toMongoArray());
        $this->lpaCollection->shouldReceive('findOne')->with(['_id'=>(int)$this->hwLpaId, 'user'=>$this->userId])->andReturn($this->hwLpa->toMongoArray());
        $this->lpaCollection->shouldReceive('findOne')->andReturn(null);
        $serviceLocatorMock->shouldReceive('get')->with('MongoDB-Default-lpa')->andReturn($this->lpaCollection);

        $this->resource = new Resource();
        $this->resource->setServiceLocator($serviceLocatorMock);
        $this->resource->setAuthorizationService($authorizationServiceMock);
        $this->resource->setRouteUser(new UserEntity($this->user));
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testGetType()
    {
        $this->assertEquals('collections', $this->resource->getType());
    }

    public function testFetchNotFound()
    {
        $entity = $this->resource->fetch(-1);
        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->status);
        $this->assertEquals('Document -1 not found for user e551d8b14c408f7efb7358fb258f1b12', $entity->detail);
    }

    public function testFetchHwLpa()
    {
        $entity = $this->resource->fetch($this->hwLpaId);
        $this->assertTrue($entity instanceof Entity);
        $this->assertEquals($this->hwLpa, $entity->getLpa());
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
        $this->assertEquals($this->userId, $createdLpa->userId());

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

    public function testPatchNullData()
    {
        $this->lpaCollection->shouldReceive('insert')->once();

        /* @var Entity */
        $patchedLpa = $this->resource->patch(null);

        $this->assertNotNull($patchedLpa);
        $this->assertGreaterThan(0, $patchedLpa->lpaId());
        $this->lpaCollection->mockery_verify();
    }

    public function testPatchMalformedData()
    {
        //The bad id value on this user will fail validation
        $user = new User();
        $user->set('id', 3);
        $this->resource->setRouteUser(new UserEntity($user));

        //So we expect an exception and for no document to be inserted
        $this->setExpectedException(\RuntimeException::class, 'A malformed LPA object was created');
        $this->lpaCollection->shouldNotReceive('insert');

        $this->resource->patch(null);
        $this->lpaCollection->mockery_verify();
    }

    public function testPatchFullLpa()
    {
        $lpa = FixturesData::getHwLpa();

        $this->lpaCollection->shouldReceive('insert')->once();

        /* @var Entity */
        $patchedLpa = $this->resource->patch($lpa->toArray());

        $this->assertNotNull($patchedLpa);
        //Id should be generated
        $this->assertNotEquals($lpa->get('id'), $patchedLpa->lpaId());
        $this->assertGreaterThan(0, $patchedLpa->lpaId());
        //User should be reassigned to logged in user
        $this->assertEquals($this->userId, $patchedLpa->userId());

        $this->lpaCollection->mockery_verify();
    }

    public function testPatchFilterIncomingData()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->set('lockedAt', new DateTime());
        $lpa->set('locked', true);

        $this->lpaCollection->shouldReceive('insert');

        /* @var Entity */
        $patchedLpa = $this->resource->patch($lpa->toArray());

        //The following properties should be maintained
        $this->assertEquals($lpa->get('document'), $patchedLpa->getLpa()->get('document'));
        $this->assertEquals($lpa->get('metadata'), $patchedLpa->getLpa()->get('metadata'));
        $this->assertEquals($lpa->get('payment'), $patchedLpa->getLpa()->get('payment'));
        $this->assertEquals($lpa->get('repeatCaseNumber'), $patchedLpa->getLpa()->get('repeatCaseNumber'));
        //All others should be ignored
        $this->assertNotEquals($lpa->get('startedAt'), $patchedLpa->getLpa()->get('startedAt'));
        $this->assertNotEquals($lpa->get('createdAt'), $patchedLpa->getLpa()->get('updatedAt'));
        $this->assertNotEquals($lpa->get('startedAt'), $patchedLpa->getLpa()->get('startedAt'));
        $this->assertNotEquals($lpa->get('completedAt'), $patchedLpa->getLpa()->get('completedAt'));
        $this->assertNotEquals($lpa->get('lockedAt'), $patchedLpa->getLpa()->get('lockedAt'));
        $this->assertNotEquals($lpa->get('user'), $patchedLpa->getLpa()->get('user'));
        $this->assertNotEquals($lpa->get('whoAreYouAnswered'), $patchedLpa->getLpa()->get('whoAreYouAnswered'));
        $this->assertNotEquals($lpa->get('locked'), $patchedLpa->getLpa()->get('locked'));
        $this->assertNotEquals($lpa->get('seed'), $patchedLpa->getLpa()->get('seed'));

        $this->lpaCollection->mockery_verify();
    }
}