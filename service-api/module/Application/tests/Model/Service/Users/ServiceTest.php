<?php

namespace ApplicationTest\Model\Service\Users;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Postgres\UserModel as CollectionUser;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\Users\Service as UsersService;
use Application\Model\Service\DataModelEntity;
use ApplicationTest\Model\Service\AbstractServiceTest;
use ApplicationTest\Model\Service\Users\ServiceBuilder;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\User\User;
use MakeSharedTest\DataModel\FixturesData;
use ArrayObject;
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

    /** @var UsersService */
    private $service;

    /** @var ServiceBuilder */
    private $serviceBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        //  Set up the services so they can be enhanced for each test
        $this->applicationsService = Mockery::mock(ApplicationsService::class);

        $this->authLogRepository = Mockery::mock(LogRepositoryInterface::class);

        $this->authUserRepository = Mockery::mock(UserRepositoryInterface::class);

        $this->serviceBuilder = new ServiceBuilder();

        $this->service = $this->serviceBuilder
            ->withApplicationsService($this->applicationsService)
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();
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

        $entity = $this->service->fetch($user->getId());
        $entityArray = $entity->toArray();

        $expectedUser = new User();
        $expectedUser->setId($user->getId());
        $expectedUser->setEmail($user->getEmail());
        $expectedUser->setCreatedAt(new DateTime($entityArray['createdAt']));
        $expectedUser->setUpdatedAt(new DateTime($entityArray['updatedAt']));
        $this->assertEquals(new DataModelEntity($expectedUser), $entity);

        $this->serviceBuilder->verify();
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

        $entity = $this->service->fetch($user->getId());

        $this->assertEquals(new DataModelEntity($user), $entity);

        $this->serviceBuilder->verify();
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

        $entity = $this->service->update([], $user->getId());
        $entityArray = $entity->toArray();

        $expectedUser = new User();
        $expectedUser->setId($user->getId());
        $expectedUser->setEmail($user->getEmail());
        $expectedUser->setCreatedAt(new DateTime($entityArray['createdAt']));
        $expectedUser->setUpdatedAt(new DateTime($entityArray['updatedAt']));
        $this->assertEquals(new DataModelEntity($expectedUser), $entity);

        $this->serviceBuilder->verify();
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

        $userUpdate = FixturesData::getUser();
        $userUpdate->getName()->setTitle('TooLong');
        $validationError = $this->service->update($userUpdate->toArray(), $user->getId());

        $this->assertTrue($validationError instanceof ValidationApiProblem);
        $this->assertEquals(400, $validationError->getStatus());
        $this->assertEquals('Your request could not be processed due to validation error', $validationError->getDetail());
        $this->assertEquals('https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md', $validationError->getType());
        $this->assertEquals('Bad Request', $validationError->getTitle());
        $validation = $validationError->validation;
        $this->assertEquals(1, count($validation));
        $this->assertTrue(array_key_exists('name.title', $validation));

        $this->serviceBuilder->verify();
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

        $userUpdate = FixturesData::getUser();
        $userUpdate->getName()->setFirst('Edited');
        $entity = $this->service->update($userUpdate->toArray(), $user->getId());
        $entityArray = $entity->toArray();

        $userUpdate->setUpdatedAt(new DateTime($entityArray['updatedAt']));
        $this->assertEquals(new DataModelEntity($userUpdate), $entity);

        $this->serviceBuilder->verify();
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

        $result = $this->service->delete($user->getId());

        $this->assertTrue($result);

        $this->serviceBuilder->verify();
    }

    public function testMatchUsers()
    {
        $query = 'smith';

        $email = FixturesData::getUser()->getEmail()->getAddress();
        $collectionUser1 = new CollectionUser(['identity' => $email]);
        $collectionUser2 = new CollectionUser(['identity' => $email]);
        $users = (new ArrayObject([$collectionUser1, $collectionUser2]))->getIterator();

        $this->authUserRepository
            ->shouldReceive('matchUsers')
            ->with($query, [])
            ->andReturn($users)
            ->once();

        $results = $this->service->matchUsers($query);

        $this->assertEquals(count($results), 2);

        $this->serviceBuilder->verify();
    }
}
