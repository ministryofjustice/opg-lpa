<?php

namespace ApplicationTest\Model\Service\Users;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Mongo\Collection\ApiUserCollection;
use Application\Model\DataAccess\Mongo\DateCallback;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\UserManagement\Service as UserManagementService;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Mockery;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use DateTime;

class ServiceTest extends AbstractServiceTest
{
    public function testFetchDoesNotExist()
    {
        $user = FixturesData::getUser();

        $apiUserCollection = Mockery::mock(ApiUserCollection::class);
        $apiUserCollection->shouldReceive('getById')->andReturn(null)->twice();
        $apiUserCollection->shouldReceive('insert')->once();

        $userManagementService = Mockery::mock(UserManagementService::class);
        $userManagementService->shouldReceive('get')->andReturn(['username' => $user->getEmail()->getAddress()]);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiUserCollection($apiUserCollection)
            ->withUserManagementService($userManagementService)
            ->build();

        $entity = $service->fetch($user->getId());
        $entityArray = $entity->toArray();

        $expectedUser = new User();
        $expectedUser->setId($user->getId());
        $expectedUser->setEmail($user->getEmail());
        $expectedUser->setCreatedAt(new DateTime($entityArray['createdAt']));
        $expectedUser->setUpdatedAt(new DateTime($entityArray['updatedAt']));
        $this->assertEquals(new DataModelEntity($expectedUser), $entity);

        $serviceBuilder->verify();
    }

    public function testFetch()
    {
        $user = FixturesData::getUser();

        $apiUserCollection = Mockery::mock(ApiUserCollection::class);
        $apiUserCollection->shouldReceive('getById')->andReturn($user->toArray(new DateCallback()))->once();
        $apiUserCollection->shouldNotReceive('insert');

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiUserCollection($apiUserCollection)
            ->build();

        $entity = $service->fetch($user->getId());

        $this->assertEquals(new DataModelEntity($user), $entity);

        $serviceBuilder->verify();
    }

    public function testUpdateNotFound()
    {
        $user = FixturesData::getUser();

        $apiUserCollection = Mockery::mock(ApiUserCollection::class);
        $apiUserCollection->shouldReceive('getById')->andReturn(null)->once();
        $apiUserCollection->shouldReceive('insert')->once();

        $userManagementService = Mockery::mock(UserManagementService::class);
        $userManagementService->shouldReceive('get')->andReturn(['username' => $user->getEmail()->getAddress()]);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiUserCollection($apiUserCollection)
            ->withUserManagementService($userManagementService)
            ->build();

        $entity = $service->update([], $user->getId());
        $entityArray = $entity->toArray();

        $expectedUser = new User();
        $expectedUser->setId($user->getId());
        $expectedUser->setEmail($user->getEmail());
        $expectedUser->setCreatedAt(new DateTime($entityArray['createdAt']));
        $expectedUser->setUpdatedAt(new DateTime($entityArray['updatedAt']));
        $this->assertEquals(new DataModelEntity($expectedUser), $entity);

        $serviceBuilder->verify();
    }

    public function testUpdateValidationFailed()
    {
        $user = FixturesData::getUser();

        $apiUserCollection = Mockery::mock(ApiUserCollection::class);
        $apiUserCollection->shouldReceive('getById')->andReturn($user->toArray(new DateCallback()))->once();
        $apiUserCollection->shouldNotReceive('update');

        $userManagementService = Mockery::mock(UserManagementService::class);
        $userManagementService->shouldReceive('get')->andReturn(['username' => $user->getEmail()->getAddress()]);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiUserCollection($apiUserCollection)
            ->withUserManagementService($userManagementService)
            ->build();

        $userUpdate = FixturesData::getUser();
        $userUpdate->getName()->setTitle('TooLong');
        $validationError = $service->update($userUpdate->toArray(), $user->getId());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('name.title', $validation));

        $serviceBuilder->verify();
    }

    public function testUpdate()
    {
        $user = FixturesData::getUser();

        $apiUserCollection = Mockery::mock(ApiUserCollection::class);
        $apiUserCollection->shouldReceive('getById')->andReturn($user->toArray(new DateCallback()))->once();
        $apiUserCollection->shouldReceive('update')->once();

        $userManagementService = Mockery::mock(UserManagementService::class);
        $userManagementService->shouldReceive('get')->andReturn(['username' => $user->getEmail()->getAddress()]);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApiUserCollection($apiUserCollection)
            ->withUserManagementService($userManagementService)
            ->build();

        $userUpdate = FixturesData::getUser();
        $userUpdate->getName()->setFirst('Edited');
        $entity = $service->update($userUpdate->toArray(), $user->getId());
        $entityArray = $entity->toArray();

        $userUpdate->setUpdatedAt(new DateTime($entityArray['updatedAt']));
        $this->assertEquals(new DataModelEntity($userUpdate), $entity);

        $serviceBuilder->verify();
    }

    public function testDelete()
    {
        $user = FixturesData::getUser();

        $applicationsService = Mockery::mock(ApplicationsService::class);
        $applicationsService->shouldReceive('deleteAll')->once();

        $apiUserCollection = Mockery::mock(ApiUserCollection::class);
        $apiUserCollection->shouldReceive('deleteById')->with($user->getId())->once();

        $userManagementService = Mockery::mock(UserManagementService::class);
        $userManagementService->shouldReceive('delete')->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationsService($applicationsService)
            ->withApiUserCollection($apiUserCollection)
            ->withUserManagementService($userManagementService)
            ->build();

        $result = $service->delete($user->getId());

        $this->assertTrue($result);

        $serviceBuilder->verify();
    }
}
