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
use Mockery;
use MongoDB\BSON\UTCDateTime;
use MongoDB\UpdateResult;
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

        $this->setFindNullLpaExpectation($user, -1);

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
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, true, false, true, false, true, false, 0);

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

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        $this->assertNotNull($patchedEntity);
        //Updated date should not have changed as the LPA document hasn't changed
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

        $this->apiLpaCollection->shouldReceive('count')
            ->withArgs([[ '_id'=>$lpa->getId(), 'locked'=>true ], [ '_id'=>true ]])->once()
            ->andReturn(1);

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('LPA has already been locked.');
        $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());
    }

    public function testPatchSetCreatedDate()
    {
        $user = FixturesData::getUser();

        $lpa = FixturesData::getHwLpa();
        $lpa->setCreatedAt(null);
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, true, true, true, false, true, true, 1);

        $this->assertNull($lpa->getCreatedAt());

        $lpa->getDocument()->setInstruction('Changed');

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
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, false, false, false, false, true, true, 1);

        //Remove primary attorneys so LPA is classed as not created
        $lpa->getDocument()->setCertificateProvider(null);

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
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, true, false, true, false, true, false, 1);

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
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, true, false, true, true, true, false, 1);

        $this->assertNull($lpa->getCompletedAt());

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertNotNull($lpaOut->getCompletedAt());
    }

    public function testPatchUpdateNumberModifiedError()
    {
        $user = FixturesData::getUser();

        $lpa = FixturesData::getHwLpa();
        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa, true, false, true, false, true, false, 2);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to update LPA. This might be because "updatedAt" has changed.');
        $this->service->patch($lpa->toArray(), $lpa->getId(), $user->getId());
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

        $this->apiLpaCollection->shouldReceive('find')
            ->withArgs([['user' => $user->getId()], ['_id' => true]])->once()
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

    private function setCreateIdExpectations()
    {
        $this->apiLpaCollection->shouldReceive('findOne')
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
        $this->apiLpaCollection->shouldReceive('insertOne')
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

        $this->apiLpaCollection->shouldReceive('count')
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

        $this->apiLpaCollection->shouldReceive('findOne')
            ->withArgs([['_id' => $originalLpa->getId()]])->once()
            ->andReturn($originalLpa->toArray(new DateCallback()));

        $updateResult = Mockery::mock(UpdateResult::class);
        $updateResult->shouldReceive('getModifiedCount')
            ->times($modifiedCount === 0 ? 1 : 2)->andReturn($modifiedCount);

        if ($setCreatedAt === true || $setCompletedAt === true || $setUpdatedAt === true) {
            $this->apiLpaCollection->shouldReceive('updateOne')
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
            $this->apiLpaCollection->shouldReceive('updateOne')
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
        $this->apiLpaCollection->shouldReceive('findOne')
            ->withArgs([$lpaFilter, ['projection' => ['_id' => true]]])->once()
            ->andReturn($isLpa === false ? null : ['_id' => $lpa->getId()]);

        if ($isLpa === true) {
            $result['updatedAt'] = new UTCDateTime();

            $this->apiLpaCollection->shouldReceive('replaceOne')
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

        $this->apiLpaCollection->shouldReceive('count')
            ->withArgs([$filter])->once()
            ->andReturn($lpasCount);

        if ($lpasCount > 0) {
            $this->apiLpaCollection->shouldReceive('find')
                ->withArgs([$filter, ['sort' => ['updatedAt' => -1], 'skip' => 0, 'limit' => 10]])
                ->andReturn(new DummyLpaMongoCursor($lpas));
        }
    }

    /**
     * @param User $user
     * @param int $lpaId
     */
    private function setFindNullLpaExpectation(User $user, int $lpaId)
    {
        $this->apiLpaCollection->shouldReceive('findOne')
            ->withArgs([['_id' => $lpaId, 'user' => $user->getId()]])->once()
            ->andReturn(null);
    }

    /**
     * @param User $user
     * @param Lpa $lpa
     */
    private function setFindOneLpaExpectation(User $user, Lpa $lpa)
    {
        $this->apiLpaCollection->shouldReceive('findOne')
            ->withArgs([['_id' => $lpa->getId(), 'user' => $user->getId()]])->once()
            ->andReturn($lpa->toArray(new DateCallback()));
    }
}
