<?php

namespace ApplicationTest\Model\Service\Users;

use Application\Model\DataAccess\Mongo\DateCallback;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Authorization\UnauthorizedException;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\Users\Service;
use Application\Model\Service\Applications\Service as ApplicationsService;
use ApplicationTest\AbstractServiceTest;
use Auth\Model\Service\UserManagementService;
use Mockery;
use MongoDB\Collection as MongoCollection;
use MongoDB\UpdateResult;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;

class ServiceTest extends AbstractServiceTest
{
    /**
     * @var Service
     */
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new Service(FixturesData::getUser()->getId(), $this->lpaCollection);

        $this->service->setLogger($this->logger);

        $this->service->setAuthorizationService($this->authorizationService);
    }

    public function testFetchCheckAccess()
    {
        $this->authorizationService->shouldReceive('isGranted')
            ->withArgs(['authenticated'])->times(1)
            ->andReturn(false);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You need to be authenticated to access this service');

        $this->service->fetch(-1);
    }

    public function testFetchDoesNotExist()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldReceive('findOne')->andReturn(null)->twice();
        $userCollection->shouldReceive('insertOne')->once();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withAuthUserCollection($userCollection)->build();

        $entity = $service->fetch($user->id);
        $entityArray = $entity->toArray();

        $expectedUser = new User();
        $expectedUser->id = $user->id;
        $expectedUser->email = $user->email;
        $expectedUser->createdAt = $entityArray['createdAt'];
        $expectedUser->updatedAt = $entityArray['updatedAt'];
        $this->assertEquals(new DataModelEntity($expectedUser), $entity);

        $serviceBuilder->verify();
    }

    public function testFetch()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldReceive('findOne')->andReturn($user->toArray(new DateCallback()))->once();
        $userCollection->shouldNotReceive('insertOne');
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withAuthUserCollection($userCollection)->build();

        $entity = $service->fetch($user->id);

        $this->assertEquals(new DataModelEntity($user), $entity);

        $serviceBuilder->verify();
    }

    public function testUpdateCheckAccess()
    {
        $this->authorizationService->shouldReceive('isGranted')
            ->withArgs(['authenticated'])->times(1)
            ->andReturn(false);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You need to be authenticated to access this service');

        $this->service->update(null, -1);
    }

    public function testUpdateNotFound()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldReceive('findOne')->andReturn(null)->once();
        $userCollection->shouldReceive('insertOne')->once();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withAuthUserCollection($userCollection)->build();

        $entity = $service->update(null, $user->id);
        $entityArray = $entity->toArray();

        $expectedUser = new User();
        $expectedUser->id = $user->id;
        $expectedUser->email = $user->email;
        $expectedUser->createdAt = $entityArray['createdAt'];
        $expectedUser->updatedAt = $entityArray['updatedAt'];
        $this->assertEquals(new DataModelEntity($expectedUser), $entity);

        $serviceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldReceive('findOne')->andReturn($user->toArray(new DateCallback()))->once();
        $userCollection->shouldNotReceive('updateOne');
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withAuthUserCollection($userCollection)->build();

        $userUpdate = FixturesData::getUser();
        $userUpdate->name->title = 'TooLong';
        $validationError = $service->update($userUpdate->toArray(), $user->id);

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->status);
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->detail);
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->type);
        $this->assertEquals('Bad Request', $validationError->title);
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('name.title', $validation));

        $serviceBuilder->verify();
    }

    public function testUpdate()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldReceive('findOne')->andReturn($user->toArray(new DateCallback()))->once();
        $updateResult = Mockery::mock(UpdateResult::class);
        $updateResult->shouldReceive('getModifiedCount')->andReturn(1);
        $userCollection->shouldReceive('updateOne')->andReturn($updateResult)->once();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withAuthUserCollection($userCollection)->build();

        $userUpdate = FixturesData::getUser();
        $userUpdate->name->first = 'Edited';
        $entity = $service->update($userUpdate->toArray(), $user->id);
        $entityArray = $entity->toArray();

        $userUpdate->updatedAt = $entityArray['updatedAt'];
        $this->assertEquals(new DataModelEntity($userUpdate), $entity);

        $serviceBuilder->verify();
    }

    public function testUpdateNumberModifiedError()
    {
        $user = FixturesData::getUser();
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldReceive('findOne')->andReturn($user->toArray(new DateCallback()))->once();
        $updateResult = Mockery::mock(UpdateResult::class);
        $updateResult->shouldReceive('getModifiedCount')->andReturn(2);
        $userCollection->shouldReceive('updateOne')->andReturn($updateResult)->once();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder->withUser(FixturesData::getUser())->withAuthUserCollection($userCollection)->build();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to update User. This might be because "updatedAt" has changed.');
        $userUpdate = FixturesData::getUser();
        $userUpdate->name->first = 'Edited';
        $service->update($userUpdate->toArray(), $user->id);

        $serviceBuilder->verify();
    }

    public function testDeleteCheckAccess()
    {
        $this->authorizationService->shouldReceive('isGranted')
            ->withArgs(['authenticated'])->times(1)
            ->andReturn(false);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('You need to be authenticated to access this service');

        $this->service->delete(-1);
    }

    public function testDelete()
    {
        $user = FixturesData::getUser();
        $applicationsService = Mockery::mock(ApplicationsService::class);
        $applicationsService->shouldReceive('deleteAll')->once();
        $userCollection = Mockery::mock(MongoCollection::class);
        $userCollection->shouldReceive('deleteOne')->with([ '_id' => $user->id ])->once();
        $userManagementService = Mockery::mock(UserManagementService::class);
        $userManagementService->shouldReceive('delete')->once();
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withUser(FixturesData::getUser())
            ->withApplicationsService($applicationsService)
            ->withAuthUserCollection($userCollection)
            ->withUserManagementService($userManagementService)
            ->build();

        $result = $service->delete($user->id);

        $this->assertTrue($result);

        $serviceBuilder->verify();
    }
}