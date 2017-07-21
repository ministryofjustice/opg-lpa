<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Authorization\UnauthorizedException;
use Application\Library\DateTime;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Applications\Collection;
use Application\Model\Rest\Applications\Entity;
use Application\Model\Rest\Applications\Resource;
use Application\Model\Rest\Applications\Resource as ApplicationsResource;
use Application\Model\Rest\Lock\LockedException;
use ApplicationTest\Model\AbstractResourceTest;
use Mockery;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use ZfcRbac\Service\AuthorizationService;

class ResourceTest extends AbstractResourceTest
{
    public function testGetRouteUserException()
    {
        $this->setExpectedException(\RuntimeException::class, 'Route User not set');
        $resource = new Resource();
        $resource->getRouteUser();
    }

    public function testSetLpa()
    {
        $pfLpa = FixturesData::getPfLpa();
        $resource = new Resource();
        $resource->setLpa($pfLpa);
        $lpa = $resource->getLpa();
        $this->assertTrue($pfLpa === $lpa);
    }

    public function testGetLpaException()
    {
        $this->setExpectedException(\RuntimeException::class, 'LPA not set');
        $resource = new Resource();
        $resource->getLpa();
    }

    public function testGetType()
    {
        $resource = new Resource();
        $this->assertEquals(AbstractResource::TYPE_COLLECTION, $resource->getType());
    }

    public function testFetchCheckAccess()
    {
        /** @var ApplicationsResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->fetch(-1);
    }

    public function testFetchNotFound()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->build();

        $entity = $resource->fetch(-1);

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->status);
        $this->assertEquals('Document -1 not found for user e551d8b14c408f7efb7358fb258f1b12', $entity->detail);

        $resourceBuilder->verify();
    }

    public function testFetchHwLpa()
    {
        $lpa = FixturesData::getHwLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpa($lpa)->build();
        
        $entity = $resource->fetch($lpa->id);
        $this->assertTrue($entity instanceof Entity);
        $this->assertEquals($lpa, $entity->getLpa());

        $resourceBuilder->verify();
    }

    public function testFetchNotAuthenticated()
    {
        $authorizationServiceMock = Mockery::mock(AuthorizationService::class);
        $authorizationServiceMock->shouldReceive('isGranted')->andReturn(false);
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withAuthorizationService($authorizationServiceMock)
            ->build();

        $this->setExpectedException(UnauthorizedException::class, 'You need to be authenticated to access this resource');
        $resource->fetch(1);

        $resourceBuilder->verify();
    }

    public function testFetchMissingPermission()
    {
        $user = FixturesData::getUser();
        $authorizationServiceMock = Mockery::mock(AuthorizationService::class);
        $authorizationServiceMock->shouldReceive('isGranted')->with('isAuthorizedToManageUser', $user->id)
            ->andReturn(false);
        $authorizationServiceMock->shouldReceive('isGranted')->andReturn(true);
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser($user)
            ->withAuthorizationService($authorizationServiceMock)
            ->build();

        $this->setExpectedException(UnauthorizedException::class, 'You do not have permission to access this resource');
        $resource->fetch(1);

        $resourceBuilder->verify();
    }

    public function testCreateCheckAccess()
    {
        /** @var ApplicationsResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->create(null);
    }

    public function testCreateNullData()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withInsert(true)->build();

        /* @var Entity */
        $createdEntity = $resource->create(null);

        $this->assertNotNull($createdEntity);
        $this->assertGreaterThan(0, $createdEntity->lpaId());

        $resourceBuilder->verify();
    }

    public function testCreateMalformedData()
    {
        //The bad id value on this user will fail validation
        $user = new User();
        $user->set('id', 3);
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser($user)->build();

        //So we expect an exception and for no document to be inserted
        $this->setExpectedException(\RuntimeException::class, 'A malformed LPA object was created');

        $resource->create(null);

        $resourceBuilder->verify();
    }

    public function testCreateFullLpa()
    {
        $user = FixturesData::getUser();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser($user)
            ->withInsert(true)
            ->build();

        $lpa = FixturesData::getHwLpa();
        /* @var Entity */
        $createdEntity = $resource->create($lpa->toArray());

        $this->assertNotNull($createdEntity);
        //Id should be generated
        $this->assertNotEquals($lpa->id, $createdEntity->lpaId());
        $this->assertGreaterThan(0, $createdEntity->lpaId());
        //User should be reassigned to logged in user
        $this->assertEquals($user->id, $createdEntity->userId());

        $resourceBuilder->verify();
    }

    public function testCreateFilterIncomingData()
    {
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withInsert(true)
            ->build();

        $lpa = FixturesData::getHwLpa();
        $lpa->set('lockedAt', new DateTime());
        $lpa->set('locked', true);

        /* @var Entity */
        $createdEntity = $resource->create($lpa->toArray());
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

        $resourceBuilder->verify();
    }

    public function testPatchCheckAccess()
    {
        /** @var ApplicationsResource $resource */
        $resource = parent::setUpCheckAccessTest(new ResourceBuilder());
        $resource->patch( null,-1);
    }

    public function testPatchValidationError()
    {
        $pfLpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa(FixturesData::getPfLpa())
            ->build();

        //Make sure the LPA is invalid
        $lpa = new Lpa();
        $lpa->id = $pfLpa->id;
        $lpa->document = new Document();
        $lpa->document->type = 'invalid';

        $validationError = $resource->patch($lpa->toArray(), $lpa->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('document.type', $validation));

        $resourceBuilder->verify();
    }

    public function testUpdateLpaValidationError()
    {
        $pfLpa = FixturesData::getPfLpa();
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder
            ->withUser(FixturesData::getUser())
            ->withLpa($pfLpa)
            ->build();

        //Make sure the LPA is invalid
        $lpa = new Lpa();
        $lpa->id = $pfLpa->id;
        $lpa->document = new Document();
        $lpa->document->type = 'invalid';

        $this->setExpectedException(\RuntimeException::class, 'LPA object is invalid');
        $resource->testUpdateLpa($lpa);

        $resourceBuilder->verify();
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

        $this->setExpectedException(LockedException::class, 'LPA has already been locked.');
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

        $this->setExpectedException(\RuntimeException::class, 'Unable to update LPA. This might be because "updatedAt" has changed.');
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
    }

    public function testFetchAllSearchByName()
    {
        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $resourceBuilder = new ResourceBuilder();
        $resource = $resourceBuilder->withUser(FixturesData::getUser())->withLpas($lpas)->build();

        /** @var Collection $response */
        $response = $resource->fetchAll(['search' => $lpas[0]->document->donor->name]);

        $this->assertEquals(0, $response->count());
    }
}