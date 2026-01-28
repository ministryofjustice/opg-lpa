<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\Users;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Model\DataAccess\Postgres\UserModel as CollectionUser;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Application\Model\DataAccess\Postgres\UserModel;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\Users\Service as UsersService;
use Application\Model\Service\DataModelEntity;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\User\User as ProfileUserModel;
use MakeSharedTest\DataModel\FixturesData;
use ArrayObject;
use DateTime;

final class ServiceTest extends AbstractServiceTestCase
{
    private MockInterface|ApplicationsService $applicationsService;
    private MockInterface|ApplicationRepositoryInterface $applicationRepository;
    private MockInterface|LogRepositoryInterface $authLogRepository;
    private MockInterface|UserRepositoryInterface $authUserRepository;
    private UsersService $service;

    protected function setUp(): void
    {
        parent::setUp();

        //  Set up the services so they can be enhanced for each test
        $this->applicationsService = Mockery::mock(ApplicationsService::class);
        $this->applicationRepository = Mockery::mock(ApplicationRepositoryInterface::class);
        $this->authLogRepository = Mockery::mock(LogRepositoryInterface::class);
        $this->authUserRepository = Mockery::mock(UserRepositoryInterface::class);

        $this->service = new UsersService();
        $this->service->setApplicationsService($this->applicationsService);
        $this->service->setApplicationRepository($this->applicationRepository);
        $this->service->setLogRepository($this->authLogRepository);
        $this->service->setUserRepository($this->authUserRepository);
    }

    public function testCreateInvalidEmail()
    {
        $this->assertEquals('invalid-username', $this->service->create('adasadsd', ''));
    }

    public function testCreateUserAlreadyExists()
    {
        $email = 'amadeupname@foo.org';
        $password = 'Pass1234';

        // expectations
        $this->authUserRepository->shouldReceive('getByUsername')
            ->with($email)
            ->andReturn(Mockery::mock(UserInterface::class));

        // test
        $result = $this->service->create($email, $password);

        // assertions
        $this->assertEquals('username-already-exists', $result);
    }

    public function testCreateInvalidPassword()
    {
        $email = 'amadeupname@foo.org';
        $password = '';

        // expectations
        $this->authUserRepository->shouldReceive('getByUsername')
            ->with($email)
            ->andReturn(null);

        // test
        $result = $this->service->create($email, $password);

        // assertions
        $this->assertEquals('invalid-password', $result);
    }

    public function testCreateSuccess()
    {
        $email = 'amadeupname@foo.org';
        $password = 'Pass1234';

        // expectations
        $this->authUserRepository->shouldReceive('getByUsername')
            ->with($email)
            ->andReturn(null);

        // NB we also include a duplicate hash in here (the false return values),
        // to exercise the while loop and ensure it works correctly
        $this->authUserRepository->shouldReceive('create')
            ->withArgs(function ($userId, $data) use ($email) {
                return $data['identity'] === $email &&
                    $data['active'] === false &&
                    is_a($data['created'], DateTime::class) &&
                    is_a($data['last_updated'], DateTime::class) &&
                    strlen($data['activation_token']) > 0 &&
                    strlen($data['password_hash']) > 0 &&
                    $data['failed_login_attempts'] === 0;
            })
            ->andReturn(false, false, true);

        // test
        $result = $this->service->create($email, $password);

        // assertions
        $this->assertIsArray($result);
        $this->assertTrue(array_key_exists('userId', $result));
        $this->assertTrue(strlen($result['activation_token']) > 0);
    }

    public function testCreateSuccessTokenHashed()
    {
        $email = 'user-hashed@foo.org';
        $password = 'Pass1234';

        // expectations
        $this->authUserRepository->shouldReceive('getByUsername')
            ->with($email)
            ->andReturn(null);

        $this->authUserRepository->shouldReceive('create')
            ->withArgs(function ($newUserId, $data) use ($email) {
                return $data['identity'] === $email &&
                    $data['active'] === false &&
                    is_a($data['created'], DateTime::class) &&
                    is_a($data['last_updated'], DateTime::class) &&
                    $data['activation_token'] === '932f64c441bf268cf590f4009eab026a097a78e0' &&
                    strlen($data['password_hash']) > 0 &&
                    $data['failed_login_attempts'] === 0;
            })
            ->andReturn(false, false, true);

        // test
        $this->service->setUseHashTokens(true);
        $result = $this->service->create($email, $password);

        // assertions
        $this->assertTrue($result['activation_token'] === '932f64c441bf268cf590f4009eab026a097a78e0');
    }

    public function testActivateNoAccount()
    {
        $this->authUserRepository->shouldReceive('activate')->andReturn(null);
        $this->assertEquals('account-not-found', $this->service->activate('foo'));

        $this->authUserRepository->shouldReceive('activate')->andReturn(false);
        $this->assertEquals('account-not-found', $this->service->activate('bar'));
    }

    public function testActivateSuccess()
    {
        $token = 'sussusssuuussss';

        // expectations
        $this->authUserRepository->shouldReceive('activate')
            ->with($token)
            ->andReturn(true);

        // assertions
        $this->assertTrue($this->service->activate($token));
    }

    public function testFetchDoesNotExist()
    {
        $user = FixturesData::getUser();

        $collectionUser = new CollectionUser(['identity' => $user->getEmail()->getAddress()]);

        $this->authUserRepository
            ->shouldReceive('getProfile')
            ->with($user->getId())
            ->andReturn(null)
            ->twice();
        $this->authUserRepository
            ->shouldReceive('getById')
            ->with($user->getId())
            ->andReturn($collectionUser)
            ->once();
        $this->authUserRepository
            ->shouldReceive('saveProfile')
            ->once();

        $entity = $this->service->fetch($user->getId());
        $entityArray = $entity->toArray();

        $expectedUser = new ProfileUserModel();
        $expectedUser->setId($user->getId());
        $expectedUser->setEmail($user->getEmail());
        $expectedUser->setCreatedAt(new DateTime($entityArray['createdAt']));
        $expectedUser->setUpdatedAt(new DateTime($entityArray['updatedAt']));
        $expectedUser->setNumberOfLpas(0);
        $this->assertEquals(new DataModelEntity($expectedUser), $entity);
    }

    public function testFetch()
    {
        $user = FixturesData::getUser();

        $this->authUserRepository
            ->shouldReceive('getProfile')
            ->with($user->getId())
            ->andReturn($user)
            ->once();
        $this->authUserRepository
            ->shouldNotReceive('saveProfile');
        $this->applicationRepository
            ->shouldReceive('count')
            ->with(['user' => $user->getId()])
            ->andReturn(3)
            ->once();

        $entity = $this->service->fetch($user->getId());

        $this->assertEquals(new DataModelEntity($user), $entity);
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

        $expectedUser = new ProfileUserModel();
        $expectedUser->setId($user->getId());
        $expectedUser->setEmail($user->getEmail());
        $expectedUser->setCreatedAt(new DateTime($entityArray['createdAt']));
        $expectedUser->setUpdatedAt(new DateTime($entityArray['updatedAt']));
        $this->assertEquals(new DataModelEntity($expectedUser), $entity);
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
        $this->assertEquals(
            [
                'type' => 'https://github.com/ministryofjustice/opg-lpa-datamodels/blob/master/docs/validation.md',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Your request could not be processed due to validation error',
                'validation' => [
                    'name.title' => ['value' => 'TooLong', 'messages' => ['must-be-less-than-or-equal:5']],
                ]
            ],
            $validationError->toArray()
        );
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
            ->shouldReceive('delete')
            ->andReturn(true);
        $this->authLogRepository
            ->shouldReceive('addLog')
            ->withArgs(function ($logDetails) use ($collectionUser) {
                $expectedHash = hash('sha512', strtolower(trim($collectionUser->username())));

                return $logDetails['reason'] === 'some-reason' &&
                    $logDetails['type'] === 'account-deleted' &&
                    $logDetails['loggedAt'] instanceof DateTime &&
                    $logDetails['identity_hash'] === $expectedHash;
            });

        $result = $this->service->delete($user->getId(), 'some-reason');

        $this->assertTrue($result);
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
    }

    public function testSearchByUsernameNotUserOrDeleted()
    {
        $username = 'ballard';
        $hashedUsername = hash('sha512', strtolower(trim($username)));

        // user isn't in user table or deletion log
        $this->authUserRepository
            ->shouldReceive('getByUsername')
            ->with($username)
            ->andReturn(null);

        $this->authLogRepository
            ->shouldReceive('getLogByIdentityHash')
            ->with($hashedUsername)
            ->andReturn(null);

        $this->assertFalse($this->service->searchByUsername($username));
    }

    public function testSearchByUsernameDeleted()
    {
        // user is in deletion log
        $username = 'shakespeare';
        $hashedUsername = hash('sha512', strtolower(trim($username)));

        $deletionLogRecord = [
            'loggedAt' => new DateTime(),
            'reason' => 'user-initiated',
        ];

        $this->authUserRepository
            ->shouldReceive('getByUsername')
            ->with($username)
            ->andReturn(null);

        $this->authLogRepository
            ->shouldReceive('getLogByIdentityHash')
            ->with($hashedUsername)
            ->andReturn($deletionLogRecord);

        $expected = [
            'isDeleted' => true,
            'deletedAt' => $deletionLogRecord['loggedAt'],
            'reason' => $deletionLogRecord['reason']
        ];

        $this->assertEquals($expected, $this->service->searchByUsername($username));
    }

    public function testSearchByUsername()
    {
        // user is in main table
        $username = 'shakespeare';

        $userRecord = new UserModel([
            'id' => $username,
        ]);

        $this->authUserRepository
            ->shouldReceive('getByUsername')
            ->with($username)
            ->andReturn($userRecord);

        $expected = $userRecord->toArray();

        $this->assertEquals($expected, $this->service->searchByUsername($username));
    }
}
