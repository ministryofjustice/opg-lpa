<?php

namespace ApplicationTest\Model\Service\Applications;

use Application\Model\DataAccess\Mongo\DateCallback;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\DateTime;
use Application\Model\Service\Applications\Collection;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\Lock\LockedException;
use ApplicationTest\Model\Service\AbstractServiceTest;
use MongoDB\BSON\UTCDateTime;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;

class ServiceTest extends AbstractServiceTest
{
    /**
     * @var TestableService
     */
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new TestableService($this->apiLpaCollection);

        $this->service->setLogger($this->logger);
    }

    public function testFetchNotFound()
    {
        $user = FixturesData::getUser();

        $this->apiLpaCollection->shouldReceive('getById')
            ->withArgs([-1, $user->getId()])
            ->once()
            ->andReturn(null);

        $entity = $this->service->fetch(-1, $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->getStatus());
        $this->assertEquals('Document -1 not found for user e551d8b14c408f7efb7358fb258f1b12', $entity->getDetail());
    }

    public function testFetchHwLpa()
    {
        $user = FixturesData::getUser();
        $lpa = FixturesData::getHwLpa();

        $this->setFindOneLpaExpectation($user, $lpa);

        $entity = $this->service->fetch($lpa->getId(), $user->getId());
        $this->assertTrue($entity instanceof DataModelEntity);
        $this->assertEquals($lpa, $entity->getData());
    }

    public function testCreateNullData()
    {
        $user = FixturesData::getUser();

        $this->setCreateIdExpectations();
        $this->setInsertOneExpectations($user);

        /* @var DataModelEntity */
        $createdEntity = $this->service->create(null, $user->getId());

        $this->assertNotNull($createdEntity);
    }

    public function testCreateMalformedData()
    {
        $user = FixturesData::getUser();

        $this->setCreateIdExpectations();

        //So we expect an exception and for no document to be inserted
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object was created');

        $this->service->create([
            'document' => [
                'type' => 'blah',
            ],
        ], $user->getId());
    }

    public function testCreateFullLpa()
    {
        $user = FixturesData::getUser();

        $this->setCreateIdExpectations();
        $this->setInsertOneExpectations($user);

        $lpa = FixturesData::getHwLpa();
        /* @var DataModelEntity */
        $createdEntity = $this->service->create($lpa->toArray(), $user->getId());

        $this->assertNotNull($createdEntity);
    }

    public function testCreateFilterIncomingData()
    {
        $user = FixturesData::getUser();

        $this->setCreateIdExpectations();
        $this->setInsertOneExpectations($user);

        $lpa = FixturesData::getHwLpa();
        $lpa->set('lockedAt', new DateTime());
        $lpa->set('locked', true);

        /* @var $createdEntity DataModelEntity */
        $createdEntity = $this->service->create($lpa->toArray(), $user->getId());
        $createdLpa = $createdEntity->getData();

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

    public function testPatchValidationError()
    {
        $user = FixturesData::getUser();
        $pfLpa = FixturesData::getPfLpa();

        $this->setFindOneLpaExpectation($user, $pfLpa);

        //Make sure the LPA is invalid
        $lpa = new Lpa();
        $lpa->setId($pfLpa->getId());
        $lpa->setDocument(new Document());
        $lpa->getDocument()->setType('invalid');

        $validationError = $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

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
        $this->service->testUpdateLpa($lpa);
    }

    public function testPatchFullLpaNoChanges()
    {
        $user = FixturesData::getUser();
        $lpa = FixturesData::getHwLpa();

        $this->setUpdateOneLpaExpectations($user, $lpa);

        /* @var DataModelEntity */
        $patchedEntity = $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        $this->assertNotNull($patchedEntity);

        //Updated date should not have changed as the LPA document hasn't changed
        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertEquals($lpa->getUpdatedAt(), $lpaOut->getUpdatedAt());
    }

    public function testPatchFullLpaChanges()
    {
        $user = FixturesData::getUser();
        $lpa = FixturesData::getHwLpa();

        $lpa->getDocument()->setInstruction('Changed');

        $this->setUpdateOneLpaExpectations($user, $lpa, FixturesData::getHwLpa());

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        $this->assertNotNull($patchedEntity);
        //Updated date should have changed as the LPA document hasn't changed
        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertNotEquals($lpa->getUpdatedAt(), $lpaOut->getUpdatedAt());
    }

    public function testPatchLockedLpa()
    {
        $user = FixturesData::getUser();
        $lpa = FixturesData::getHwLpa();

        $lpa->setLocked(true);

        $this->setFindOneLpaExpectation($user, $lpa);

        $this->logger->shouldReceive('info')
            ->withArgs(['Updating LPA', ['lpaid' => $lpa->getId()]])->once();

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('LPA has already been locked.');
        $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());
    }

    public function testPatchSetCreatedDate()
    {
        $user = FixturesData::getUser();
        $lpa = FixturesData::getHwLpa();

        $lpa->getDocument()->setInstruction('Changed');
        $lpa->setCreatedAt(null);

        $this->setUpdateOneLpaExpectations($user, $lpa, FixturesData::getHwLpa());

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertNotNull($lpaOut->getCreatedAt());
    }

    public function testPatchNotCreatedYet()
    {
        $user = FixturesData::getUser();
        $lpa = FixturesData::getHwLpa();

        //Remove primary attorneys so LPA is classed as not created
        $lpa->getDocument()->setCertificateProvider(null);

        $this->setUpdateOneLpaExpectations($user, $lpa, FixturesData::getHwLpa());

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertNull($lpaOut->getCreatedAt());
    }

    public function testPatchSetCompletedAtNotLocked()
    {
        $user = FixturesData::getUser();
        $lpa = FixturesData::getHwLpa();

        $lpa->setCompletedAt(null);
        $lpa->setLocked(false);

        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa);

        $this->assertNull($lpa->getCompletedAt());

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertNull($lpaOut->getCompletedAt());
    }

    public function testPatchSetCompletedAtLocked()
    {
        $user = FixturesData::getUser();
        $lpa = FixturesData::getHwLpa();

        $lpa->setCompletedAt(null);
        $lpa->setLocked(true);

        $this->setUpdateOneLpaExpectations($user, $lpa, FixturesData::getHwLpa());

        $this->assertNull($lpa->getCompletedAt());

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertNotNull($lpaOut->getCompletedAt());
    }

    public function testPatchFilterIncomingData()
    {
        $user = FixturesData::getUser();
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

        $this->setUpdateOneLpaExpectations($user, $lpa, FixturesData::getHwLpa());

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        $patchedLpa = $patchedEntity->getData();

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

    public function testDeleteNotFound()
    {
        $user = FixturesData::getUser();

        $this->setDeleteExpectations($user, -1, null);

        $response = $this->service->delete(-1, $user->getId());

        $this->assertTrue($response instanceof ApiProblem);
        $this->assertEquals(404, $response->getStatus());
        $this->assertEquals('Document not found', $response->getDetail());
    }

    public function testDelete()
    {
        $user = FixturesData::getUser();
        $lpa = FixturesData::getPfLpa();

        $this->setDeleteExpectations($user, $lpa->getId(), $lpa);

        $response = $this->service->delete($lpa->getId(), $user->getId());

        $this->assertTrue($response);
    }

    public function testDeleteAll()
    {
        $user = FixturesData::getUser();
        $lpa = FixturesData::getPfLpa();

        $this->setDeleteExpectations($user, $lpa->getId(), $lpa);

        $this->apiLpaCollection->shouldReceive('fetchByUserId')
            ->withArgs([$user->getId()])
            ->once()
            ->andReturn([['_id' => $lpa->getId()]]);

        $response = $this->service->deleteAll($user->getId());

        $this->assertTrue($response);
    }

    public function testFetchAllNoRecords()
    {
        $user = FixturesData::getUser();

        $this->setFetchAllExpectations(['user' => $user->getId()], []);

        /** @var Collection $response */
        $response = $this->service->fetchAll($user->getId());

        $this->assertEquals(0, $response->count());
    }

    public function testFetchAllOneRecord()
    {
        $user = FixturesData::getUser();

        $lpas = [FixturesData::getHwLpa()];
        $this->setFetchAllExpectations(['user' => $user->getId()], $lpas);

        /** @var Collection $response */
        $response = $this->service->fetchAll($user->getId());

        $this->assertEquals(1, $response->count());
        $apiLpaCollection = $response->toArray();

        $this->assertEquals(1, $apiLpaCollection['total']);
        $applications = $apiLpaCollection['applications'];
        $this->assertEquals(1, count($applications));

        $this->assertEquals(($lpas[0])->abbreviatedToArray(), $applications[0]);
    }

    public function testFetchAllSearchById()
    {
        $user = FixturesData::getUser();

        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $this->setFetchAllExpectations(['user' => $user->getId(), '_id' => $lpas[1]->id], [$lpas[1]]);

        /** @var Collection $response */
        $response = $this->service->fetchAll($user->getId(), ['search' => $lpas[1]->id]);

        $this->assertEquals(1, $response->count());
        $apiLpaCollection = $response->toArray();

        $this->assertEquals(1, $apiLpaCollection['total']);
        $applications = $apiLpaCollection['applications'];
        $this->assertEquals(1, count($applications));

        $this->assertEquals(($lpas[1])->abbreviatedToArray(), $applications[0]);
    }

    public function testFetchAllSearchByIdAndFilter()
    {
        $user = FixturesData::getUser();

        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $this->setFetchAllExpectations([
            'search' => $lpas[1]->id,
            'filter' => ['user' => 'missing'],
            'user' => $user->getId(),
            '_id' => $lpas[1]->id
        ], []);

        /** @var Collection $response */
        $response = $this->service->fetchAll($user->getId(), ['search' => $lpas[1]->id, 'filter' => ['user' => 'missing']]);

        $this->assertEquals(0, $response->count());
    }

    public function testFetchAllSearchByReference()
    {
        $user = FixturesData::getUser();

        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $this->setFetchAllExpectations(['user' => $user->getId(), '_id' => $lpas[0]->id], [$lpas[0]]);

        /** @var Collection $response */
        $response = $this->service->fetchAll($user->getId(), ['search' => Formatter::id($lpas[0]->id)]);

        $this->assertEquals(1, $response->count());
        $apiLpaCollection = $response->toArray();

        $this->assertEquals(1, $apiLpaCollection['total']);
        $applications = $apiLpaCollection['applications'];
        $this->assertEquals(1, count($applications));

        $this->assertEquals(($lpas[0])->abbreviatedToArray(), $applications[0]);
    }

    public function testFetchAllSearchByName()
    {
        $user = FixturesData::getUser();

        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];
        $this->setFetchAllExpectations([
            'user' => $user->getId(),
            'search' => [
                '$regex' => '.*' . $lpas[0]->document->donor->name . '.*',
                '$options' => 'i',
            ],
        ], []);

        /** @var Collection $response */
        $response = $this->service->fetchAll($user->getId(), ['search' => $lpas[0]->document->donor->name]);

        $this->assertEquals(0, $response->count());
    }

    /**
     * @param User $user
     * @param Lpa $lpa
     */
    private function setFindOneLpaExpectation(User $user, Lpa $lpa)
    {
        //  Return with or without user ID
        $this->apiLpaCollection->shouldReceive('getById')
            ->withArgs([$lpa->getId(), $user->getId()])
            ->andReturn($lpa->toArray(new DateCallback()));
        $this->apiLpaCollection->shouldReceive('getById')
            ->withArgs([$lpa->getId()])
            ->andReturn($lpa->toArray(new DateCallback()));
    }

    private function setCreateIdExpectations()
    {
        $this->apiLpaCollection->shouldReceive('getById')
            ->once()
            ->andReturn(null);
    }

    /**
     * @param User $user
     */
    private function setInsertOneExpectations(User $user)
    {
        $this->apiLpaCollection->shouldReceive('insert')
            ->withArgs(function ($lpa) use ($user) {
                /** @var Lpa $lpa */
                return is_int($lpa->getId())
                    && $lpa->getId() >= 1000000 && $lpa->getId() <= 99999999999
                    && $lpa->getStartedAt() instanceof \DateTime
                    && $lpa->getUpdatedAt() instanceof \DateTime
                    && $lpa->getUser() === $user->getId()
                    && !$lpa->isLocked()
                    && !$lpa->isWhoAreYouAnswered()
                    && ($lpa->getDocument() instanceof Document);
            })
            ->once()
            ->andReturn(null);
    }

    private function setUpdateOneLpaExpectations(User $user, Lpa $lpa, Lpa $existingLpa = null)
    {
        //  If an existing (from Mongo) version of the LPA has not been provided then just use the LPA passed
        if (is_null($existingLpa)) {
            $existingLpa = $lpa;
        }

        $this->setFindOneLpaExpectation($user, $existingLpa);

        $this->logger->shouldReceive('info')
            ->withArgs(['Updating LPA', ['lpaid' => $lpa->getId()]])->once();

        $this->apiLpaCollection->shouldReceive('update')
            ->once()
            ->andReturnUsing(function (Lpa $lpaIn, $updateTimestamp) {
                if ($lpaIn->isStateCreated()) {
                    if (!$lpaIn->getCreatedAt() instanceof DateTime) {
                        $lpaIn->setCreatedAt(new DateTime());
                    }
                } else {
                    $lpaIn->setCreatedAt(null);
                }

                // If completed, record the date.
                if ($lpaIn->isStateCompleted()) {
                    // If we don't already have a complete date and the LPA is locked...
                    if (!$lpaIn->getCompletedAt() instanceof DateTime && $lpaIn->isLocked()) {
                        $lpaIn->setCompletedAt(new DateTime());
                    }
                } else {
                    $lpaIn->setCompletedAt(null);
                }

                if ($updateTimestamp === true) {
                    // Record the time we updated the document.
                    $lpaIn->setUpdatedAt(new DateTime());
                }
            });

        $this->logger->shouldReceive('info')
            ->withArgs(function ($message, $extra) use ($lpa) {
                return $message == 'LPA updated successfully'
                    && $extra['lpaid'] == $lpa->getId()
                    && $lpa->getUpdatedAt() instanceof \DateTime;
            })
            ->once();
    }

    /**
     * @param User $user
     * @param int $lpaId
     * @param Lpa $lpa
     */
    private function setDeleteExpectations(User $user, int $lpaId, $lpa)
    {
        $isLpa = ($lpa instanceof Lpa) === true;
        $this->apiLpaCollection->shouldReceive('getById')
            ->withArgs([$lpaId, $user->getId()])
            ->once()
            ->andReturn($isLpa === false ? null : ['_id' => $lpa->getId()]);

        if ($isLpa === true) {
            $result['updatedAt'] = new UTCDateTime();

            $this->apiLpaCollection->shouldReceive('deleteById')
                ->withArgs([$lpaId, $user->getId()])
                ->once();
        }
    }

    /**
     * @param [] $filter
     * @param Lpa[] $lpas
     */
    private function setFetchAllExpectations($filter, array $lpas)
    {
        $lpasCount = count($lpas);

        $this->apiLpaCollection->shouldReceive('fetch')
            ->withArgs([$filter])
            ->andReturn(new DummyLpaMongoCursor($lpas));

        if ($lpasCount > 0) {
            $this->apiLpaCollection->shouldReceive('fetch')
                ->withArgs([$filter, ['sort' => ['updatedAt' => -1], 'skip' => 0, 'limit' => 10]])
                ->andReturn(new DummyLpaMongoCursor($lpas));
        }
    }
}
