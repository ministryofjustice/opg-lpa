<?php

namespace ApplicationTest\Model\Rest\Applications;

use Application\DataAccess\Mongo\DateCallback;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Authorization\UnauthorizedException;
use Application\Library\DateTime;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Applications\AbbreviatedEntity;
use Application\Model\Rest\Applications\Collection;
use Application\Model\Rest\Applications\Entity;
use Application\Model\Rest\Lock\LockedException;
use ApplicationTest\AbstractResourceTest;
use Mockery;
use MongoDB\BSON\UTCDateTime;
use MongoDB\UpdateResult;
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
        $this->assertEquals(404, $entity->getStatus());
        $this->assertEquals('Document -1 not found for user e551d8b14c408f7efb7358fb258f1b12', $entity->getDetail());
    }

    public function testFetchHwLpa()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $lpa = FixturesData::getHwLpa();
        $this->setFindOneLpaExpectation($user, $lpa);

        $entity = $this->resource->fetch($lpa->getId());
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
        $this->setCheckAccessExpectations($this->resource, $user, true, false, true);

        $lpa = FixturesData::getHwLpa();
        $this->setFindOneLpaExpectation($user, $lpa);

        $entity = $this->resource->fetch($lpa->getId());
        $this->assertTrue($entity instanceof Entity);
        $this->assertEquals($lpa, $entity->getLpa());
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
        $this->assertNotEquals($lpa->getId(), $createdEntity->lpaId());
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
        $this->setCheckAccessExpectations($this->resource, $user, true, true, false, 2);

        $pfLpa = FixturesData::getPfLpa();
        $this->setFindOneLpaExpectation($user, $pfLpa);

        //Make sure the LPA is invalid
        $lpa = new Lpa();
        $lpa->setId($pfLpa->getId());
        $lpa->setDocument(new Document());
        $lpa->getDocument()->setType('invalid');

        $validationError = $this->resource->patch($lpa->toArray(), $lpa->getId());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals(
            'Your request could not be processed due to validation error',
            $validationError->getDetail()
        );
        $this->assertEquals(
            'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
            $validationError->getType()
        );
        $this->assertEquals('Bad Request', $validationError->getTitle());
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
            ->withArgs(['Updating LPA', ['lpaid' => $pfLpa->getId()]])->once();

        //Make sure the LPA is invalid
        $lpa = new Lpa();
        $lpa->setId($pfLpa->getId());
        $lpa->setDocument(new Document());
        $lpa->getDocument()->setType('invalid');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LPA object is invalid');
        $this->resource->testUpdateLpa($lpa);
    }

    public function testPatchFullLpaNoChanges()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, true, false, 3);

        $lpa = FixturesData::getHwLpa();
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, true, false, true, false, true, false, 0);

        /* @var Entity */
        $patchedEntity = $this->resource->patch($lpa->toArray(), $lpa->getId());

        $this->assertNotNull($patchedEntity);
        //Id should be retained
        $this->assertEquals($lpa->getId(), $patchedEntity->lpaId());
        //User should not be reassigned to logged in user
        $this->assertEquals($lpa->getUser(), $patchedEntity->userId());
        //Updated date should not have changed as the LPA document hasn't changed
        $this->assertEquals($lpa->getUpdatedAt(), $patchedEntity->getLpa()->getUpdatedAt());
    }

    public function testPatchFullLpaChanges()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, true, false, 3);

        $lpa = FixturesData::getHwLpa();
        $lpa->getDocument()->setInstruction('Changed');
        $this->setUpdateOneLpaExpectations(
            $user,
            FixturesData::getHwLpa(),
            $lpa,
            true,
            false,
            true,
            false,
            true,
            true,
            1
        );

        /* @var Entity */
        $patchedEntity = $this->resource->patch($lpa->toArray(), $lpa->getId());

        $this->assertNotNull($patchedEntity);
        //Id should be retained
        $this->assertEquals($lpa->getId(), $patchedEntity->lpaId());
        //User should not be reassigned to logged in user
        $this->assertEquals($lpa->getUser(), $patchedEntity->userId());
        //Updated date should not have changed as the LPA document hasn't changed
        $this->assertNotEquals($lpa->getUpdatedAt(), $patchedEntity->getLpa()->getUpdatedAt());
    }

    public function testPatchLockedLpa()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, true, false, 3);

        $lpa = FixturesData::getHwLpa();
        $lpa->setLocked(true);
        $this->setFindOneLpaExpectation($user, $lpa);

        $this->logger->shouldReceive('info')
            ->withArgs(['Updating LPA', ['lpaid' => $lpa->getId()]])->once();

        $this->lpaCollection->shouldReceive('count')
            ->withArgs([[ '_id'=>$lpa->getId(), 'locked'=>true ], [ '_id'=>true ]])->once()
            ->andReturn(1);

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('LPA has already been locked.');
        $this->resource->patch($lpa->toArray(), $lpa->getId());
    }

    public function testPatchSetCreatedDate()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, true, false, 3);

        $lpa = FixturesData::getHwLpa();
        $lpa->setCreatedAt(null);
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, true, true, true, false, true, true, 1);

        $this->assertNull($lpa->getCreatedAt());

        /* @var Entity */
        $lpa->getDocument()->setInstruction('Changed');
        $patchedEntity = $this->resource->patch($lpa->toArray(), $lpa->getId());

        $this->assertNotNull($patchedEntity->getLpa()->getCreatedAt());
    }

    public function testPatchNotCreatedYet()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, true, false, 3);

        $lpa = FixturesData::getHwLpa();
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, false, false, false, false, true, true, 1);

        /* @var Entity */
        //Remove primary attorneys so LPA is classed as not created
        $lpa->getDocument()->setCertificateProvider(null);
        $patchedEntity = $this->resource->patch($lpa->toArray(), $lpa->getId());

        $this->assertNull($patchedEntity->getLpa()->getCreatedAt());
    }

    public function testPatchSetCompletedAtNotLocked()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, true, false, 3);

        $lpa = FixturesData::getHwLpa();
        $lpa->setCompletedAt(null);
        $lpa->setLocked(false);
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, true, false, true, false, true, false, 1);

        $this->assertNull($lpa->getCompletedAt());

        /* @var Entity */
        $patchedEntity = $this->resource->patch($lpa->toArray(), $lpa->getId());

        $this->assertNull($patchedEntity->getLpa()->getCompletedAt());
    }

    public function testPatchSetCompletedAtLocked()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, true, false, 3);

        $lpa = FixturesData::getHwLpa();
        $lpa->setCompletedAt(null);
        $lpa->setLocked(true);
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, true, false, true, true, true, false, 1);

        $this->assertNull($lpa->getCompletedAt());

        /* @var Entity */
        $patchedEntity = $this->resource->patch($lpa->toArray(), $lpa->getId());

        $this->assertNotNull($patchedEntity->getLpa()->getCompletedAt());
    }

    public function testPatchUpdateNumberModifiedError()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, true, false, 3);

        $lpa = FixturesData::getHwLpa();
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, true, false, true, false, true, false, 2);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to update LPA. This might be because "updatedAt" has changed.');
        $this->resource->patch($lpa->toArray(), $lpa->getId());
    }

    public function testPatchFilterIncomingData()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, true, false, 3);

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
        $this->setUpdateOneLpaExpectations(
            $user,
            FixturesData::getHwLpa(),
            $lpa,
            true,
            false,
            true,
            false,
            true,
            false,
            1
        );

        /* @var Entity */
        $patchedEntity = $this->resource->patch($lpa->toArray(), $lpa->getId());
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
    }

    public function testDeleteCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->delete(-1);
    }

    public function testDeleteNotFound()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $this->setDeleteExpectations($user, -1, null);

        $response = $this->resource->delete(-1);

        $this->assertTrue($response instanceof ApiProblem);
        $this->assertEquals(404, $response->getStatus());
        $this->assertEquals('Document not found', $response->getDetail());
    }

    public function testDelete()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $lpa = FixturesData::getPfLpa();
        $this->setDeleteExpectations($user, $lpa->getId(), $lpa);

        $response = $this->resource->delete($lpa->getId());

        $this->assertTrue($response);
    }

    public function testDeleteAllCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->deleteAll();
    }

    public function testDeleteAll()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user, true, true, false, 2);

        $lpa = FixturesData::getPfLpa();
        $this->setDeleteExpectations($user, $lpa->getId(), $lpa);

        $this->lpaCollection->shouldReceive('find')
            ->withArgs([['user' => $user->getId()], ['_id' => true]])->once()
            ->andReturn([['_id' => $lpa->getId()]]);

        $response = $this->resource->deleteAll();

        $this->assertTrue($response);
    }

    public function testFetchAllCheckAccess()
    {
        $this->setUpCheckAccessTest($this->resource);

        $this->resource->fetchAll();
    }

    public function testFetchAllNoRecords()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $this->setFetchAllExpectations(['user' => $user->getId()], []);

        /** @var Collection $response */
        $response = $this->resource->fetchAll();

        $this->assertEquals(0, $response->count());
    }

    public function testFetchAllOneRecord()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $lpas = [FixturesData::getHwLpa()];
        $this->setFetchAllExpectations(['user' => $user->getId()], $lpas);

        /** @var Collection $response */
        $response = $this->resource->fetchAll();

        $this->assertEquals(1, $response->count());
        $lpaCollection = $response->toArray();
        $this->assertEquals(1, $lpaCollection['count']);
        /** @var AbbreviatedEntity[] $items */
        $items = $lpaCollection['items'];
        $this->assertEquals(1, count($items));
        $this->assertEquals($lpas[0], $items[0]->getLpa());
    }

    public function testFetchAllSearchById()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $this->setFetchAllExpectations(['user' => $user->getId(), '_id' => $lpas[1]->id], [$lpas[1]]);

        /** @var Collection $response */
        $response = $this->resource->fetchAll(['search' => $lpas[1]->id]);

        $this->assertEquals(1, $response->count());
        $lpaCollection = $response->toArray();
        $this->assertEquals(1, $lpaCollection['count']);
        /** @var AbbreviatedEntity[] $items */
        $items = $lpaCollection['items'];
        $this->assertEquals(1, count($items));
        $this->assertEquals($lpas[1], $items[0]->getLpa());
    }

    public function testFetchAllSearchByIdAndFilter()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $this->setFetchAllExpectations([
            'search' => $lpas[1]->id,
            'filter' => ['user' => 'missing'],
            'user' => $user->getId(),
            '_id' => $lpas[1]->id
        ], []);

        /** @var Collection $response */
        $response = $this->resource->fetchAll(['search' => $lpas[1]->id, 'filter' => ['user' => 'missing']]);

        $this->assertEquals(0, $response->count());
    }

    public function testFetchAllSearchByReference()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $this->setFetchAllExpectations(['user' => $user->getId(), '_id' => $lpas[0]->id], [$lpas[0]]);

        /** @var Collection $response */
        $response = $this->resource->fetchAll(['search' => Formatter::id($lpas[0]->id)]);

        $this->assertEquals(1, $response->count());
        $lpaCollection = $response->toArray();
        $this->assertEquals(1, $lpaCollection['count']);
        /** @var AbbreviatedEntity[] $items */
        $items = $lpaCollection['items'];
        $this->assertEquals(1, count($items));
        $this->assertEquals($lpas[0], $items[0]->getLpa());
    }

    public function testFetchAllSearchByName()
    {
        $user = FixturesData::getUser();
        $this->setCheckAccessExpectations($this->resource, $user);

        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $this->setFetchAllExpectations([
            'user' => $user->getId(),
            'search' => [
                '$regex' => '.*' . $lpas[0]->document->donor->name . '.*',
                '$options' => 'i',
            ],
        ], []);

        /** @var Collection $response */
        $response = $this->resource->fetchAll(['search' => $lpas[0]->document->donor->name]);

        $this->assertEquals(0, $response->count());
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
     * @param User $user
     */
    private function setInsertOneExpectations(User $user)
    {
        $this->lpaCollection->shouldReceive('insertOne')
            ->withArgs(function ($document) use ($user) {
                return is_int($document['_id']) && $document['_id'] >= 1000000 && $document['_id'] <= 99999999999
                    && $document['startedAt'] instanceof UTCDateTime
                    && $document['updatedAt'] instanceof UTCDateTime
                    && $document['user'] === $user->getId()
                    && $document['locked'] === false
                    && $document['whoAreYouAnswered'] === false
                    && is_array($document['document']) && empty($document['document']) === false;
            })->once()->andReturn(null);
    }

    /**
     * @param User $user
     * @param Lpa $originalLpa
     * @param Lpa $updatedLpa
     * @param bool $isCreated
     * @param bool $setCreatedAt
     * @param bool $isCompleted
     * @param bool $setCompletedAt
     * @param bool $setSearchField
     * @param bool $setUpdatedAt
     * @param int $modifiedCount
     */
    private function setUpdateOneLpaExpectations(
        User $user,
        Lpa $originalLpa,
        Lpa $updatedLpa,
        bool $isCreated,
        bool $setCreatedAt,
        bool $isCompleted,
        bool $setCompletedAt,
        bool $setSearchField,
        bool $setUpdatedAt,
        int $modifiedCount
    ) {
        $this->setFindOneLpaExpectation($user, $originalLpa);

        $this->logger->shouldReceive('info')
            ->withArgs(['Updating LPA', ['lpaid' => $updatedLpa->getId()]])->once();

        $this->lpaCollection->shouldReceive('count')
            ->withArgs([[ '_id'=>$updatedLpa->getId(), 'locked'=>true ], [ '_id'=>true ]])->once()
            ->andReturn(0);

        if ($isCreated === true) {
            $this->logger->shouldReceive('info')
                ->withArgs(['LPA is created', ['lpaid' => $updatedLpa->getId()]])->once();

            if ($setCreatedAt === true) {
                $this->logger->shouldReceive('info')
                    ->withArgs(['Setting created time for existing LPA', ['lpaid' => $updatedLpa->getId()]])->once();
            }
        } else {
            $this->logger->shouldReceive('info')
                ->withArgs(['LPA is not fully created', ['lpaid' => $updatedLpa->getId()]])->once();

            $updatedLpa->setCreatedAt(null);
        }

        if ($isCompleted === true) {
            $this->logger->shouldReceive('info')
                ->withArgs(['LPA is complete', ['lpaid' => $updatedLpa->getId()]])->once();

            if ($setCompletedAt) {
                $this->logger->shouldReceive('info')
                    ->withArgs(['Setting completed time for existing LPA', ['lpaid' => $updatedLpa->getId()]])->once();
            }
        } else {
            $this->logger->shouldReceive('info')
                ->withArgs(['LPA is not complete', ['lpaid' => $updatedLpa->getId()]])->once();

            $updatedLpa->setCompletedAt(null);
        }

        $searchField = null;
        if ($setSearchField === true) {
            $searchField = $updatedLpa->getDocument()->getDonor()->getName();

            $this->logger->shouldReceive('info')
                ->withArgs(['Setting search field', [
                    'lpaid' => $updatedLpa->getId(),
                    'searchField' => $searchField
                ]])->once();
        }

        if ($setUpdatedAt === true) {
            $this->logger->shouldReceive('info')->withArgs(function ($message, $extra) use ($updatedLpa) {
                return $message === 'Setting updated time'
                    && $extra['lpaid'] === $updatedLpa->getId()
                    && $extra['updatedAt'] > new DateTime('-1 minute');
            })->once();
        }

        $this->lpaCollection->shouldReceive('findOne')
            ->withArgs([['_id' => $originalLpa->getId()]])->once()
            ->andReturn($originalLpa->toArray(new DateCallback()));

        $updateResult = Mockery::mock(UpdateResult::class);
        $updateResult->shouldReceive('getModifiedCount')
            ->times($modifiedCount === 0 ? 1 : 2)->andReturn($modifiedCount);

        if ($setCreatedAt === true || $setCompletedAt === true || $setUpdatedAt === true) {
            $this->lpaCollection->shouldReceive('updateOne')
                ->withArgs(function (
                    $filter,
                    $update,
                    $options
                ) use (
                    $originalLpa,
                    $updatedLpa,
                    $searchField,
                    $setCreatedAt,
                    $setCompletedAt,
                    $setUpdatedAt
                ) {
                    $set = $update['$set'];
                    $updatedLpaArray = array_merge(
                        $updatedLpa->toArray(new DateCallback()),
                        ['search' => $searchField->__toString()]
                    );

                    if ($setCreatedAt) {
                        unset($set['createdAt']);
                        unset($updatedLpaArray['createdAt']);
                    }

                    if ($setCompletedAt) {
                        unset($set['completedAt']);
                        unset($updatedLpaArray['completedAt']);
                    }

                    if ($setUpdatedAt === true) {
                        unset($set['updatedAt']);
                        unset($updatedLpaArray['updatedAt']);
                    }

                    return $filter == [
                            '_id' => $updatedLpa->getId(),
                            'updatedAt' => new UTCDateTime($originalLpa->getUpdatedAt())
                        ] && ($setCreatedAt === false
                            || $update['$set']['createdAt'] > new UTCDateTime(new DateTime('-1 minute')))
                        && ($setCompletedAt === false
                            || $update['$set']['completedAt'] > new UTCDateTime(new DateTime('-1 minute')))
                        && ($setUpdatedAt === false
                            || $update['$set']['updatedAt'] > new UTCDateTime(new DateTime('-1 minute')))
                        && $set == $updatedLpaArray
                        && $options == ['upsert' => false, 'multiple' => false];
                })->once()
                ->andReturn($updateResult);
        } else {
            $this->lpaCollection->shouldReceive('updateOne')
                ->withArgs([
                    ['_id' => $updatedLpa->getId(), 'updatedAt' => new UTCDateTime($originalLpa->getUpdatedAt())],
                    ['$set' => array_merge($originalLpa->toArray(new DateCallback()), ['search' => $searchField])],
                    ['upsert' => false, 'multiple' => false]
                ])->once()
                ->andReturn($updateResult);
        }

        if ($modifiedCount === 0 || $modifiedCount === 1) {
            if ($setUpdatedAt) {
                $this->logger->shouldReceive('info')->withArgs(function ($message, $extra) use ($updatedLpa) {
                    return $message === 'LPA updated successfully'
                        && $extra['lpaid'] === $updatedLpa->getId()
                        && $extra['updatedAt'] > new DateTime('-1 minute');
                })->once();
            } else {
                $this->logger->shouldReceive('info')
                    ->withArgs(['LPA updated successfully', [
                        'lpaid' => $updatedLpa->getId(),
                        'updatedAt' => $originalLpa->getUpdatedAt()
                    ]])->once();
            }
        }
    }

    /**
     * @param User $user
     * @param int $lpaId
     * @param Lpa $lpa
     */
    private function setDeleteExpectations(User $user, int $lpaId, $lpa)
    {
        $isLpa = ($lpa instanceof Lpa) === true;
        $lpaFilter = ['_id' => $lpaId, 'user' => $user->getId()];
        $this->lpaCollection->shouldReceive('findOne')
            ->withArgs([$lpaFilter, ['projection' => ['_id' => true]]])->once()
            ->andReturn($isLpa === false ? null : ['_id' => $lpa->getId()]);

        if ($isLpa === true) {
            $result['updatedAt'] = new UTCDateTime();

            $this->lpaCollection->shouldReceive('replaceOne')
                ->withArgs(function ($filter, $replacement) use ($lpaFilter) {
                    return $filter == $lpaFilter
                        && $replacement['updatedAt'] > new UTCDateTime(new DateTime('-1 minute'));
                })->once();
        }
    }

    /**
     * @param [] $filter
     * @param Lpa[] $lpas
     */
    private function setFetchAllExpectations($filter, array $lpas)
    {
        $lpasCount = count($lpas);

        $this->lpaCollection->shouldReceive('count')
            ->withArgs([$filter])->once()
            ->andReturn($lpasCount);

        if ($lpasCount > 0) {
            $this->lpaCollection->shouldReceive('find')
                ->withArgs([$filter, ['sort' => ['updatedAt' => -1], 'skip' => 0, 'limit' => 250]])
                ->andReturn(new DummyLpaMongoCursor($lpas));
        }
    }
}
