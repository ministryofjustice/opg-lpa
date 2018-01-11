<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Authorization\UnauthorizedException;
use Application\Library\DateTime;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Applications\Collection;
use Application\Model\Rest\Applications\Entity;
use Application\Model\Rest\Applications\Resource as ApplicationsResource;
use Application\Model\Rest\Lock\LockedException;
use ApplicationTest\AbstractResourceTest;
use MongoDB\BSON\UTCDateTime;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;

class ResourceTest extends AbstractResourceTest
{
    /**
     * @var TestableResource
     */
    private $resource;

    protected function setUp()
    {
        parent::setUp();

        $this->resource = new TestableResource($this->lpaCollection);

        $this->resource->setLogger($this->logger);

        $this->resource->setAuthorizationService($this->authorizationService);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('lpaId', $this->resource->getIdentifier());
    }

    public function testGetName()
    {
        $this->assertEquals('applications', $this->resource->getName());
    }

    public function testGetRouteUserException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Route User not set');
        $this->resource->getRouteUser();
    }

    public function testSetLpa()
    {
        $pfLpa = FixturesData::getPfLpa();
        $this->resource->setLpa($pfLpa);
        $lpa = $this->resource->getLpa();
        $this->assertTrue($pfLpa === $lpa);
    }

    public function testGetLpaException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LPA not set');
        $this->resource->getLpa();
    }

    public function testGetType()
    {
        $this->assertEquals(AbstractResource::TYPE_COLLECTION, $this->resource->getType());
    }

    public function testFetchCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->fetch(-1);
    }

    public function testFetchNotFound()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $this->setFindNullLpaExpectation($user, -1);

        $entity = $this->resource->fetch(-1);

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->status);
        $this->assertEquals('Document -1 not found for user e551d8b14c408f7efb7358fb258f1b12', $entity->detail);
    }

    public function testFetchHwLpa()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $lpa = FixturesData::getHwLpa();
        $this->setFindOneLpaExpectation($user, $lpa);

        $entity = $this->resource->fetch($lpa->id);
        $this->assertTrue($entity instanceof Entity);
        $this->assertEquals($lpa, $entity->getLpa());
    }

    public function testFetchNotAuthenticated()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, false);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You need to be authenticated to access this resource');

        $this->resource->fetch(1);
    }

    public function testFetchMissingPermission()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, false);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You do not have permission to access this resource');

        $this->resource->fetch(1);
    }

    public function testFetchMissingPermissionAdmin()
    {
        $user = FixturesData::getUser();
        $authorizationServiceMock = Mockery::mock(AuthorizationService::class);
        $authorizationServiceMock->shouldReceive('isGranted')->with('isAuthorizedToManageUser', $user->id)
            ->andReturn(false);
        $authorizationServiceMock->shouldReceive('isGranted')->with('authenticated')->andReturn(true);
        $authorizationServiceMock->shouldReceive('isGranted')->with('admin')->andReturn(true);
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser($user)
            ->withAuthorizationService($authorizationServiceMock)
            ->build();

        $resource->fetch(1);

        $resourceBuilder->verify();
    }

    public function testCreateCheckAccess()
    {
        parent::setUpCheckAccessTest($this->resource);

        $this->resource->create(null);
    }

    public function testCreateNullData()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $this->setCreateIdExpectations();

        $this->setInsertOneExpectations($user);

        /* @var Entity */
        $createdEntity = $this->resource->create(null);

        $this->assertNotNull($createdEntity);
        $this->assertGreaterThan(0, $createdEntity->lpaId());
    }

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $user = new User();
        $user->set('id', 3);
        $this->setCheckAccessExpectations($this->resource, $user);

        $this->setCreateIdExpectations();

        //So we expect an exception and for no document to be inserted
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object was created');

        $this->resource->create(null);
    }

    public function testCreateFullLpa()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $this->setCreateIdExpectations();

        $this->setInsertOneExpectations($user);

        $lpa = FixturesData::getHwLpa();
        /* @var Entity */
        $createdEntity = $this->resource->create($lpa->toArray());

        $this->assertNotNull($createdEntity);
        //Id should be generated
        $this->assertNotEquals($lpa->id, $createdEntity->lpaId());
    }

    public function testCreateFilterIncomingData()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $this->setCreateIdExpectations();

        $this->setInsertOneExpectations($user);

        $lpa = FixturesData::getHwLpa();
        $lpa->set('lockedAt', new DateTime());
        $lpa->set('locked', true);

        /* @var Entity */
        $createdEntity = $this->resource->create($lpa->toArray());
        $createdLpa = $createdEntity->getLpa();

        //The following properties should be maintained
        $this->assertEquals($lpa->get('document'), $createdLpa->get('document'));
        $this->assertEquals($lpa->get('metadata'), $createdLpa->get('metadata'));
        $this->assertEquals($lpa->get('payment'), $createdLpa->get('payment'));
        $this->assertEquals($lpa->get('repeatCaseNumber'), $createdLpa->get('repeatCaseNumber'));
        //All others should be ignored
        $this->assertNotEquals($lpa->get('startedAt'), $createdLpa->get('startedAt'));
        $this->assertNotEquals($lpa->get('createdAt'), $createdLpa->get('createdAt'));
        $this->assertNotEquals($lpa->get('updatedAt'), $createdLpa->get('updatedAt'));
        $this->assertNotEquals($lpa->get('completedAt'), $createdLpa->get('completedAt'));
        $this->assertNotEquals($lpa->get('lockedAt'), $createdLpa->get('lockedAt'));
        $this->assertNotEquals($lpa->get('user'), $createdLpa->get('user'));
        $this->assertNotEquals($lpa->get('whoAreYouAnswered'), $createdLpa->get('whoAreYouAnswered'));
        $this->assertNotEquals($lpa->get('locked'), $createdLpa->get('locked'));
        $this->assertNotEquals($lpa->get('seed'), $createdLpa->get('seed'));
    }

    public function testPatchCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->patch(null, -1);
    }

    public function testPatchValidationError()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, true, 2);

        $pfLpa = FixturesData::getPfLpa();
        $this->setFindOneLpaExpectation($user, $pfLpa);

        //Make sure the LPA is invalid
        $lpa = new Lpa();
        $lpa->id = $pfLpa->id;
        $lpa->document = new Document();
        $lpa->document->type = 'invalid';

        $validationError = $this->resource->patch($lpa->toArray(), $lpa->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('document.type', $validation));
    }

    public function testUpdateLpaValidationError()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $pfLpa = FixturesData::getPfLpa();
        $this->logger->shouldReceive('info')
            ->withArgs(['Updating LPA', ['lpaid' => $pfLpa->id]])->once();

        //Make sure the LPA is invalid
        $lpa = new Lpa();
        $lpa->id = $pfLpa->id;
        $lpa->document = new Document();
        $lpa->document->type = 'invalid';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LPA object is invalid');
        $this->resource->testUpdateLpa($lpa);
    }

    public function testPatchFullLpaNoChanges()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa(FixturesData::getHwLpa())
            ->withUpdateNumberModified(0)
            ->build();

        $lpa = FixturesData::getHwLpa();
        /* @var Entity */
        $patchedEntity = $resource->patch($lpa->toArray(), $lpa->id);

        $this->assertNotNull($patchedEntity);
        //Id should be retained
        $this->assertEquals($lpa->id, $patchedEntity->lpaId());
        //User should not be reassigned to logged in user
        $this->assertEquals($lpa->user, $patchedEntity->userId());
        //Updated date should not have changed as the LPA document hasn't changed
        $this->assertEquals($lpa->updatedAt, $patchedEntity->getLpa()->updatedAt);

        $resourceBuilder->verify();
    }

    public function testPatchFullLpaChanges()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa(FixturesData::getHwLpa())
            ->withUpdateNumberModified(1)
            ->build();

        $lpa = FixturesData::getHwLpa();
        $lpa->document->instruction = 'Changed';
        /* @var Entity */
        $patchedEntity = $resource->patch($lpa->toArray(), $lpa->id);

        $this->assertNotNull($patchedEntity);
        //Id should be retained
        $this->assertEquals($lpa->id, $patchedEntity->lpaId());
        //User should not be reassigned to logged in user
        $this->assertEquals($lpa->user, $patchedEntity->userId());
        //Updated date should not have changed as the LPA document hasn't changed
        $this->assertNotEquals($lpa->updatedAt, $patchedEntity->getLpa()->updatedAt);

        $resourceBuilder->verify();
    }

    public function testPatchLockedLpa()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa(FixturesData::getPfLpa())
            ->withLocked(true)
            ->build();

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('LPA has already been locked.');
        $lpa = FixturesData::getPfLpa();
        $resource->patch($lpa->toArray(), $lpa->id);

        $resourceBuilder->verify();
    }

    public function testPatchSetCreatedDate()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->createdAt = null;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $this->assertNull($lpa->createdAt);

        /* @var Entity */
        $patchedEntity = $resource->patch($lpa->toArray(), $lpa->id);

        $this->assertNotNull($patchedEntity->getLpa()->createdAt);

        $resourceBuilder->verify();
    }

    public function testPatchNotCreatedYet()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa(FixturesData::getHwLpa())
            ->withUpdateNumberModified(1)
            ->build();

        $lpa = FixturesData::getHwLpa();
        //Remove primary attorneys so LPA is classed as not created
        $lpa->document->certificateProvider = null;
        /* @var Entity */
        $patchedEntity = $resource->patch($lpa->toArray(), $lpa->id);

        $this->assertNull($patchedEntity->getLpa()->createdAt);

        $resourceBuilder->verify();
    }

    public function testPatchSetCompletedAtNotLocked()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->completedAt = null;
        $lpa->locked = false;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $this->assertNull($lpa->completedAt);

        /* @var Entity */
        $patchedEntity = $resource->patch($lpa->toArray(), $lpa->id);

        $this->assertNull($patchedEntity->getLpa()->completedAt);

        $resourceBuilder->verify();
    }

    public function testPatchSetCompletedAtLocked()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->completedAt = null;
        $lpa->locked = true;
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($lpa)
            ->withUpdateNumberModified(1)
            ->build();

        $this->assertNull($lpa->completedAt);

        /* @var Entity */
        $patchedEntity = $resource->patch($lpa->toArray(), $lpa->id);

        $this->assertNotNull($patchedEntity->getLpa()->completedAt);

        $resourceBuilder->verify();
    }

    public function testPatchUpdateNumberModifiedError()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa(FixturesData::getHwLpa())
            ->withUpdateNumberModified(2)
            ->build();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to update LPA. This might be because "updatedAt" has changed.');
        $resource->patch($lpa->toArray(), $lpa->id);

        $resourceBuilder->verify();
    }

    public function testPatchFilterIncomingData()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->set('startedAt', new DateTime());
        $lpa->set('createdAt', new DateTime());
        $lpa->set('updatedAt', new DateTime());
        $lpa->set('completedAt', new DateTime());
        $lpa->set('user', 'changed');
        $lpa->set('whoAreYouAnswered', false);
        $lpa->set('lockedAt', new DateTime());
        $lpa->set('locked', true);
        $lpa->set('seed', 'changed');
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa(FixturesData::getHwLpa())
            ->withUpdateNumberModified(1)
            ->build();

        /* @var Entity */
        $patchedEntity = $resource->patch($lpa->toArray(), $lpa->id);
        $patchedLpa = $patchedEntity->getLpa();

        //The following properties should be maintained
        $this->assertEquals($lpa->get('document'), $patchedLpa->get('document'));
        $this->assertEquals($lpa->get('metadata'), $patchedLpa->get('metadata'));
        $this->assertEquals($lpa->get('payment'), $patchedLpa->get('payment'));
        $this->assertEquals($lpa->get('repeatCaseNumber'), $patchedLpa->get('repeatCaseNumber'));
        //All others should be ignored
        $this->assertNotEquals($lpa->get('startedAt'), $patchedLpa->get('startedAt'));
        $this->assertNotEquals($lpa->get('createdAt'), $patchedLpa->get('createdAt'));
        $this->assertNotEquals($lpa->get('updatedAt'), $patchedLpa->get('updatedAt'));
        $this->assertNotEquals($lpa->get('completedAt'), $patchedLpa->get('completedAt'));
        $this->assertNotEquals($lpa->get('lockedAt'), $patchedLpa->get('lockedAt'));
        $this->assertNotEquals($lpa->get('user'), $patchedLpa->get('user'));
        $this->assertNotEquals($lpa->get('whoAreYouAnswered'), $patchedLpa->get('whoAreYouAnswered'));
        $this->assertNotEquals($lpa->get('locked'), $patchedLpa->get('locked'));
        $this->assertNotEquals($lpa->get('seed'), $patchedLpa->get('seed'));

        $resourceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        /** @var ApplicationsResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->delete(-1);
    }

    public function testDeleteNotFound()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->build();

        $response = $resource->delete(-1);

        $this->assertTrue($response instanceof ApiProblem);
        $this->assertEquals(404, $response->status);
        $this->assertEquals('Document not found', $response->detail);

        $resourceBuilder->verify();
    }

    public function testDelete()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withToDelete($lpa)->build();

        $response = $resource->delete($lpa->id);

        $this->assertTrue($response);

        $resourceBuilder->verify();
    }

    public function testDeleteAllCheckAccess()
    {
        /** @var ApplicationsResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->deleteAll();
    }

    public function testDeleteAll()
    {
        $lpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withToDelete($lpa)->build();

        $response = $resource->deleteAll();

        $this->assertTrue($response);

        $resourceBuilder->verify();
    }

    public function testFetchAllCheckAccess()
    {
        /** @var ApplicationsResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetchAll();
    }

    public function testFetchAllNoRecords()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->build();

        /** @var Collection $response */
        $response = $resource->fetchAll();

        $this->assertEquals(0, $response->count());

        $resourceBuilder->verify();
    }

    public function testFetchAllOneRecord()
    {
        $lpas = [FixturesData::getHwLpa()];
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpas($lpas)->build();

        /** @var Collection $response */
        $response = $resource->fetchAll();

        $this->assertEquals(1, $response->count());
        $lpaCollection = $response->toArray();
        $this->assertEquals(1, $lpaCollection['count']);
        $this->assertEquals(1, count($lpaCollection['items']));
        $this->assertEquals($lpas[0], $lpaCollection['items'][0]->getLpa());

        $resourceBuilder->verify();
    }

    public function testFetchAllSearchById()
    {
        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpas($lpas)->build();

        /** @var Collection $response */
        $response = $resource->fetchAll(['search' => $lpas[1]->id]);

        $this->assertEquals(1, $response->count());
        $lpaCollection = $response->toArray();
        $this->assertEquals(1, $lpaCollection['count']);
        $this->assertEquals(1, count($lpaCollection['items']));
        $this->assertEquals($lpas[1], $lpaCollection['items'][0]->getLpa());

        $resourceBuilder->verify();
    }

    public function testFetchAllSearchByIdAndFilter()
    {
        $user = FixturesData::getUser();
        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser($user)->withLpas($lpas)->build();

        /** @var Collection $response */
        $response = $resource->fetchAll(['search' => $lpas[1]->id, 'filter' => ['user' => 'missing']]);

        $this->assertEquals(0, $response->count());

        $resourceBuilder->verify();
    }

    public function testFetchAllSearchByReference()
    {
        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpas($lpas)->build();

        /** @var Collection $response */
        $response = $resource->fetchAll(['search' => Formatter::id($lpas[0]->id)]);

        $this->assertEquals(1, $response->count());
        $lpaCollection = $response->toArray();
        $this->assertEquals(1, $lpaCollection['count']);
        $this->assertEquals(1, count($lpaCollection['items']));
        $this->assertEquals($lpas[0], $lpaCollection['items'][0]->getLpa());

        $resourceBuilder->verify();
    }

    public function testFetchAllSearchByName()
    {
        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpas($lpas)->build();

        /** @var Collection $response */
        $response = $resource->fetchAll(['search' => $lpas[0]->document->donor->name]);

        $this->assertEquals(0, $response->count());

        $resourceBuilder->verify();
    }

    private function setCreateIdExpectations()
    {
        $this->lpaCollection->shouldReceive('findOne')
            ->withArgs(function ($filter, $options) {
                return is_int($filter['_id']) && $filter['_id'] >= 1000000 && $filter['_id'] <= 99999999999
                    && $options['_id'] === true;
            })->once()->andReturn(null);
    }

    /**
     * @param $user
     */
    private function setInsertOneExpectations($user)
    {
        $this->lpaCollection->shouldReceive('insertOne')
            ->withArgs(function ($document) use ($user) {
                return is_int($document['_id']) && $document['_id'] >= 1000000 && $document['_id'] <= 99999999999
                    && $document['startedAt'] instanceof UTCDateTime
                    && $document['updatedAt'] instanceof UTCDateTime
                    && $document['user'] === $user->id
                    && $document['locked'] === false
                    && $document['whoAreYouAnswered'] === false
                    && is_array($document['document']) && empty($document['document']) === false;
            })->once()->andReturn(null);
    }
}