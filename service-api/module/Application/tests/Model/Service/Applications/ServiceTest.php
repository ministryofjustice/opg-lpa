<?php

namespace ApplicationTest\Model\Service\Applications;

use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Mongo\DateCallback;
use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\DateTime;
use Application\Model\Service\Applications\Collection;
use Application\Model\Service\DataModelEntity;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Opg\Lpa\DataModel\Lpa\Formatter;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use Mockery;

class ServiceTest extends AbstractServiceTest
{
    /**
     * @var MockInterface|ApplicationRepositoryInterface
     */
    private $applicationRepository;

    /**
     * @var TestableService
     */
    private $service;

    protected function setUp()
    {
        parent::setUp();

        //  Set up the ApiLpaCollection so it can be enhanced for each test
        $this->applicationRepository = Mockery::mock(ApplicationRepositoryInterface::class);
    }

    public function testFetchNotFound()
    {
        $user = FixturesData::getUser();

        $this->applicationRepository->shouldReceive('getById')
            ->withArgs([-1, $user->getId()])
            ->once()
            ->andReturn(null);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        $entity = $service->fetch(-1, $user->getId());

        $this->assertTrue($entity instanceof ApiProblem);
        $this->assertEquals(404, $entity->getStatus());
        $this->assertEquals('Document -1 not found for user e551d8b14c408f7efb7358fb258f1b12', $entity->getDetail());
    }

    public function testFetchHwLpa()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->setFindOneLpaExpectation($user, $lpa);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        $entity = $service->fetch($lpa->getId(), $user->getId());
        $this->assertTrue($entity instanceof DataModelEntity);
        $this->assertEquals($lpa, $entity->getData());
    }

    public function testCreateNullData()
    {
        $user = FixturesData::getUser();

        $this->setCreateIdExpectations();
        $this->setInsertOneExpectations($user);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /* @var DataModelEntity */
        $createdEntity = $service->create(null, $user->getId());

        $this->assertNotNull($createdEntity);
    }

    public function testCreateMalformedData()
    {
        $user = FixturesData::getUser();

        $this->setCreateIdExpectations();

        //So we expect an exception and for no document to be inserted
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A malformed LPA object was created');

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        $service->create([
            'document' => [
                'type' => 'blah',
            ],
        ], $user->getId());
    }

    public function testCreateFullLpa()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->setCreateIdExpectations();
        $this->setInsertOneExpectations($user);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /* @var DataModelEntity */
        $createdEntity = $service->create($lpa->toArray(), $user->getId());

        $this->assertNotNull($createdEntity);
    }

    public function testCreateFilterIncomingData()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->set('lockedAt', new DateTime());
        $lpa->set('locked', true);

        $user = FixturesData::getUser();

        $this->setCreateIdExpectations();
        $this->setInsertOneExpectations($user);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /* @var $createdEntity DataModelEntity */
        $createdEntity = $service->create($lpa->toArray(), $user->getId());
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
        $pfLpa = FixturesData::getPfLpa();

        $user = FixturesData::getUser();

        $this->setFindOneLpaExpectation($user, $pfLpa);

        //Make sure the LPA is invalid
        $lpa = new Lpa();
        $lpa->setId($pfLpa->getId());
        $lpa->setDocument(new Document());
        $lpa->getDocument()->setType('invalid');

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        $validationError = $service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

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

        //Make sure the LPA is invalid
        $lpa = new Lpa();
        $lpa->setId($pfLpa->getId());
        $lpa->setDocument(new Document());
        $lpa->getDocument()->setType('invalid');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('LPA object is invalid');

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        $service->testUpdateLpa($lpa);
    }

    public function testPatchFullLpaNoChanges()
    {
        $lpa = FixturesData::getHwLpa();

        $user = FixturesData::getUser();

        $this->setUpdateOneLpaExpectations($user, $lpa);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /* @var DataModelEntity */
        $patchedEntity = $service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        $this->assertNotNull($patchedEntity);

        //Updated date should not have changed as the LPA document hasn't changed
        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertEquals($lpa->getUpdatedAt(), $lpaOut->getUpdatedAt());
    }

    public function testPatchFullLpaChanges()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->getDocument()->setInstruction('Changed');

        $user = FixturesData::getUser();

        $this->setUpdateOneLpaExpectations($user, $lpa, FixturesData::getHwLpa());

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        $this->assertNotNull($patchedEntity);
        //Updated date should have changed as the LPA document hasn't changed
        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertNotEquals($lpa->getUpdatedAt(), $lpaOut->getUpdatedAt());
    }

    public function testPatchSetCreatedDate()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->getDocument()->setInstruction('Changed');
        $lpa->setCreatedAt(null);

        $user = FixturesData::getUser();

        $this->setUpdateOneLpaExpectations($user, $lpa, FixturesData::getHwLpa());

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertNotNull($lpaOut->getCreatedAt());
    }

    public function testPatchNotCreatedYet()
    {
        $lpa = FixturesData::getHwLpa();
        //Remove primary attorneys so LPA is classed as not created
        $lpa->getDocument()->setCertificateProvider(null);

        $user = FixturesData::getUser();

        $this->setUpdateOneLpaExpectations($user, $lpa, FixturesData::getHwLpa());

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertNull($lpaOut->getCreatedAt());
    }

    public function testPatchSetCompletedAtNotLocked()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->setCompletedAt(null);
        $lpa->setLocked(false);

        $user = FixturesData::getUser();

        $this->setUpdateOneLpaExpectations($user, $lpa, $lpa);

        $this->assertNull($lpa->getCompletedAt());

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertNull($lpaOut->getCompletedAt());
    }

    public function testPatchSetCompletedAtLocked()
    {
        $lpa = FixturesData::getHwLpa();
        $lpa->setCompletedAt(null);
        $lpa->setLocked(true);

        $user = FixturesData::getUser();

        $this->setUpdateOneLpaExpectations($user, $lpa, FixturesData::getHwLpa());

        $this->assertNull($lpa->getCompletedAt());

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

        /** @var Lpa $lpaOut */
        $lpaOut = $patchedEntity->getData();

        $this->assertNotNull($lpaOut->getCompletedAt());
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

        $user = FixturesData::getUser();

        $this->setUpdateOneLpaExpectations($user, $lpa, FixturesData::getHwLpa());

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /* @var $patchedEntity DataModelEntity */
        $patchedEntity = $service->patch($lpa->toArray(), $lpa->getId(), $user->getId());

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

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        $response = $service->delete(-1, $user->getId());

        $this->assertTrue($response instanceof ApiProblem);
        $this->assertEquals(404, $response->getStatus());
        $this->assertEquals('Document not found', $response->getDetail());
    }

    public function testDelete()
    {
        $lpa = FixturesData::getPfLpa();

        $user = FixturesData::getUser();

        $this->setDeleteExpectations($user, $lpa->getId(), $lpa);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        $response = $service->delete($lpa->getId(), $user->getId());

        $this->assertTrue($response);
    }

    public function testDeleteAll()
    {
        $lpa = FixturesData::getPfLpa();

        $user = FixturesData::getUser();

        $this->setDeleteExpectations($user, $lpa->getId(), $lpa);

        $this->applicationRepository->shouldReceive('fetchByUserId')
            ->withArgs([$user->getId()])
            ->once()
            ->andReturn(new \ArrayIterator([['_id' => $lpa->getId()]]));

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        $response = $service->deleteAll($user->getId());

        $this->assertTrue($response);
    }

    public function testFetchAllNoRecords()
    {
        $user = FixturesData::getUser();

        $this->setFetchAllExpectations(['user' => $user->getId()], []);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /** @var Collection $response */
        $response = $service->fetchAll($user->getId());

        $this->assertEquals(0, $response->count());
    }

    public function testFetchAllOneRecord()
    {
        $lpas = [FixturesData::getHwLpa()];

        $user = FixturesData::getUser();

        $this->setFetchAllExpectations(['user' => $user->getId()], $lpas);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /** @var Collection $response */
        $response = $service->fetchAll($user->getId());

        $this->assertEquals(1, $response->count());
        $apiLpaCollection = $response->toArray();

        $this->assertEquals(1, $apiLpaCollection['total']);
        $applications = $apiLpaCollection['applications'];
        $this->assertEquals(1, count($applications));

        $this->assertEquals(($lpas[0])->abbreviatedToArray(), $applications[0]);
    }

    public function testFetchAllSearchById()
    {
        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];

        $user = FixturesData::getUser();

        $this->setFetchAllExpectations(['user' => $user->getId(), '_id' => $lpas[1]->id], [$lpas[1]]);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /** @var Collection $response */
        $response = $service->fetchAll($user->getId(), ['search' => $lpas[1]->id]);

        $this->assertEquals(1, $response->count());
        $apiLpaCollection = $response->toArray();

        $this->assertEquals(1, $apiLpaCollection['total']);
        $applications = $apiLpaCollection['applications'];
        $this->assertEquals(1, count($applications));

        $this->assertEquals(($lpas[1])->abbreviatedToArray(), $applications[0]);
    }

    public function testFetchAllSearchByIdAndFilter()
    {
        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];

        $user = FixturesData::getUser();

        $this->setFetchAllExpectations([
            'search' => $lpas[1]->id,
            'filter' => ['user' => 'missing'],
            'user' => $user->getId(),
            '_id' => $lpas[1]->id
        ], []);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /** @var Collection $response */
        $response = $service->fetchAll($user->getId(), ['search' => $lpas[1]->id, 'filter' => ['user' => 'missing']]);

        $this->assertEquals(0, $response->count());
    }

    public function testFetchAllSearchByReference()
    {
        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];

        $user = FixturesData::getUser();

        $this->setFetchAllExpectations(['user' => $user->getId(), '_id' => $lpas[0]->id], [$lpas[0]]);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /** @var Collection $response */
        $response = $service->fetchAll($user->getId(), ['search' => Formatter::id($lpas[0]->id)]);

        $this->assertEquals(1, $response->count());
        $apiLpaCollection = $response->toArray();

        $this->assertEquals(1, $apiLpaCollection['total']);
        $applications = $apiLpaCollection['applications'];
        $this->assertEquals(1, count($applications));

        $this->assertEquals(($lpas[0])->abbreviatedToArray(), $applications[0]);
    }

    public function testFetchAllSearchByName()
    {
        $lpas = [FixturesData::getHwLpa(), FixturesData::getPfLpa()];

        $user = FixturesData::getUser();

        $this->setFetchAllExpectations([
            'user' => $user->getId(),
            'search' => [
                '$regex' => '.*' . $lpas[0]->document->donor->name . '.*',
                '$options' => 'i',
            ],
        ], []);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationRepository($this->applicationRepository)
            ->build();

        /** @var Collection $response */
        $response = $service->fetchAll($user->getId(), ['search' => $lpas[0]->document->donor->name]);

        $this->assertEquals(0, $response->count());
    }

    /**
     * @param User $user
     * @param Lpa $lpa
     */
    private function setFindOneLpaExpectation(User $user, Lpa $lpa)
    {
        //  Return with or without user ID
        $this->applicationRepository->shouldReceive('getById')
            ->withArgs([$lpa->getId(), $user->getId()])
            ->andReturn($lpa->toArray(new DateCallback()) + ['id'=>$lpa->getId()]);
        $this->applicationRepository->shouldReceive('getById')
            ->withArgs([$lpa->getId()])
            ->andReturn($lpa->toArray(new DateCallback()) + ['id'=>$lpa->getId()]);
    }

    private function setCreateIdExpectations()
    {
        $this->applicationRepository->shouldReceive('getById')
            ->once()
            ->andReturn(null);
    }

    /**
     * @param User $user
     */
    private function setInsertOneExpectations(User $user)
    {
        $this->applicationRepository->shouldReceive('insert')
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
            ->andReturn(true);
    }

    private function setUpdateOneLpaExpectations(User $user, Lpa $lpa, Lpa $existingLpa = null)
    {
        //  If an existing (from Mongo) version of the LPA has not been provided then just use the LPA passed
        if (is_null($existingLpa)) {
            $existingLpa = $lpa;
        }

        $this->setFindOneLpaExpectation($user, $existingLpa);

        $this->applicationRepository->shouldReceive('update')
            ->once()
            ->andReturnUsing(function (Lpa $lpaIn) use ($existingLpa) {

                $updateTimestamp = true;
                if (!is_null($existingLpa)) {
                    $updateTimestamp = !$lpaIn->equalsIgnoreMetadata($existingLpa);
                }

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

                return true;
            });
    }

    /**
     * @param User $user
     * @param int $lpaId
     * @param Lpa $lpa
     */
    private function setDeleteExpectations(User $user, int $lpaId, $lpa)
    {
        $isLpa = ($lpa instanceof Lpa) === true;
        $this->applicationRepository->shouldReceive('getById')
            ->withArgs([$lpaId, $user->getId()])
            ->once()
            ->andReturn($isLpa === false ? null : ['_id' => $lpa->getId()]);

        if ($isLpa === true) {
            $this->applicationRepository->shouldReceive('deleteById')
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

        $this->applicationRepository->shouldReceive('count')
            ->withArgs([$filter])
            ->andReturn($lpasCount);

        if ($lpasCount > 0) {

            $lpasArray = array_map(function (Lpa $lpa){
                return $lpa->toArray(new DateCallback());
            }, $lpas);

            $this->applicationRepository->shouldReceive('fetch')
                ->withArgs([$filter, ['sort' => ['updatedAt' => -1], 'skip' => 0, 'limit' => 10]])
                ->andReturn(new \ArrayIterator($lpasArray));
        }
    }
}
