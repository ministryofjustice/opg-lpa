<?php

namespace ApplicationTest\Model\Service\Users;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Postgres\UserModel as CollectionUser;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\DataModelEntity;
use ApplicationTest\Model\Service\AbstractServiceTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use DateTime;

class ServiceTest extends AbstractServiceTest
{
    /**
     * @var MockInterface|ApplicationsService
     */
    private $applicationsService;

    /**
     * @var MockInterface|LogRepositoryInterface
     */
    private $authLogRepository;

    /**
     * @var MockInterface|UserRepositoryInterface
     */
    private $authUserRepository;

    protected function setUp()
    {
        parent::setUp();

        //  Set up the services so they can be enhanced for each test
        $this->applicationsService = Mockery::mock(ApplicationsService::class);

        $this->authLogRepository = Mockery::mock(LogRepositoryInterface::class);

        $this->authUserRepository = Mockery::mock(UserRepositoryInterface::class);
    }


    public function testFetchDoesNotExist()
    {
        $user = FixturesData::getUser();

        $collectionUser = new CollectionUser(['identity' => $user->getEmail()->getAddress()]);

        $this->authUserRepository
            ->shouldReceive('getProfile')
            ->andReturn(null)
            ->twice();
        $this->authUserRepository
            ->shouldReceive('getById')
            ->andReturn($collectionUser)
            ->once();
        $this->authUserRepository
            ->shouldReceive('saveProfile')
            ->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationsService($this->applicationsService)
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
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

        $this->authUserRepository
            ->shouldReceive('getProfile')
            ->andReturn($user)
            ->once();
        $this->authUserRepository
            ->shouldNotReceive('saveProfile');

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationsService($this->applicationsService)
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $entity = $service->fetch($user->getId());

        $this->assertEquals(new DataModelEntity($user), $entity);

        $serviceBuilder->verify();
    }

    public function testUpdateNotFound()
    {
        $user = FixturesData::getUser();

        $collectionUser = new CollectionUser(['identity' => $user->getEmail()->getAddress()]);

        $this->authUserRepository
            ->shouldReceive('getProfile')
            ->andReturn(null)
            ->once();
        $this->authUserRepository
            ->shouldReceive('getById')
            ->andReturn($collectionUser)
            ->once();
        $this->authUserRepository
            ->shouldReceive('saveProfile')
            ->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationsService($this->applicationsService)
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
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

        $collectionUser = new CollectionUser(['identity' => $user->getEmail()->getAddress()]);

        $this->authUserRepository
            ->shouldReceive('getProfile')
            ->andReturn($user)
            ->once();
        $this->authUserRepository
            ->shouldReceive('getById')
            ->andReturn($collectionUser)
            ->once();
        $this->authUserRepository
            ->shouldNotReceive('saveProfile');

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationsService($this->applicationsService)
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
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

        $collectionUser = new CollectionUser(['identity' => $user->getEmail()->getAddress()]);

        $this->authUserRepository
            ->shouldReceive('getProfile')
            ->andReturn($user)
            ->once();
        $this->authUserRepository
            ->shouldReceive('getById')
            ->andReturn($collectionUser)
            ->once();
        $this->authUserRepository
            ->shouldReceive('saveProfile');

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationsService($this->applicationsService)
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
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

        $collectionUser = new CollectionUser(['identity' => $user->getEmail()->getAddress()]);

        $this->applicationsService
            ->shouldReceive('deleteAll')
            ->once();
        $this->authUserRepository
            ->shouldReceive('getById')
            ->andReturn($collectionUser)
            ->once();
        $this->authUserRepository
            ->shouldReceive('delete');

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withApplicationsService($this->applicationsService)
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->delete($user->getId());

        $this->assertTrue($result);

        $serviceBuilder->verify();
    }
}
