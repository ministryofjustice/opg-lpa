<?php

namespace ApplicationTest\Model\Service\UserManagement;

use Application\Model\DataAccess\Mongo\Collection\AuthLogCollection;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\DataAccess\Mongo\Collection\User;
use ApplicationTest\Model\Service\AbstractServiceTest;
use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mockery;
use Mockery\MockInterface;

class ServiceTest extends AbstractServiceTest
{
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
        $this->authLogRepository = Mockery::mock(LogRepositoryInterface::class);

        $this->authUserRepository = Mockery::mock(UserRepositoryInterface::class);
    }

    public function testGetUserNotFound()
    {
        $this->setUserDataSourceGetByIdExpectation(1, null);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->get(1);

        $this->assertEquals('user-not-found', $result);
    }

    public function testGetSuccess()
    {
        $this->setUserDataSourceGetByIdExpectation(1, new User([
            '_id' => 1,
            'identity' => 'unit@test.com',
            'active' => true,
            'last_login' => new DateTime('2018-01-08 09:10:11')
        ]));

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->get(1);

        $this->assertEquals([
            'userId' => 1,
            'username' => 'unit@test.com',
            'isActive' => true,
            'lastLoginAt' => new DateTime('2018-01-08 09:10:11'),
            'updatedAt' => false,
            'createdAt' => false,
            'activatedAt' => false,
            'lastFailedLoginAttemptAt' => false,
            'failedLoginAttempts' => 0
        ], $result);
    }

    public function testGetByUsernameNullNotDeleted()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $this->setLogDataSourceGetLogByIdentityHashExpectation('unit@test.com', null);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->getByUsername('unit@test.com');

        $this->assertEquals(false, $result);
    }

    public function testGetByUsernameNullDeleted()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $this->setLogDataSourceGetLogByIdentityHashExpectation('unit@test.com', [
            'loggedAt' => new DateTime('2018-01-08 09:10:11'),
            'reason' => 'expired'
        ]);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->getByUsername('unit@test.com');

        $this->assertEquals([
            'isDeleted' => true,
            'deletedAt' => new DateTime('2018-01-08 09:10:11'),
            'reason' => 'expired'
        ], $result);
    }

    public function testGetByUsernameSuccess()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User(['_id' => 1]));

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->getByUsername('unit@test.com');

        $this->assertEquals([
            'userId' => 1,
            'username' => null,
            'isActive' => false,
            'lastLoginAt' => false,
            'updatedAt' => false,
            'createdAt' => false,
            'activatedAt' => false,
            'lastFailedLoginAttemptAt' => false,
            'failedLoginAttempts' => 0
        ], $result);
    }

    public function testCreateInvalidUsername()
    {
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->create('invalid', 'Password123');

        $this->assertEquals('invalid-username', $result);
    }

    public function testCreateUsernameAlreadyExists()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([]));

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->create('unit@test.com', 'Password123');

        $this->assertEquals('username-already-exists', $result);
    }

    public function testCreateUsernameInvalidPassword()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->create('unit@test.com', 'invalid');

        $this->assertEquals('invalid-password', $result);
    }

    public function testCreateUsernameSuccess()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $this->authUserRepository->shouldReceive('create')
            ->withArgs(function ($id, $details) {
                //Store generated token details for later validation
                $this->tokenDetails = [
                    'userId' => $id,
                    'activation_token' => $details['activation_token'],
                ];

                return strlen($id) > 20
                    && $details['identity'] === 'unit@test.com'
                    && $details['active'] === false
                    && strlen($details['activation_token']) > 20
                    && password_verify('Password123', $details['password_hash'])
                    && $details['created'] <= new DateTime()
                    && $details['last_updated'] <= new DateTime()
                    && $details['failed_login_attempts'] === 0;
            })
            ->twice()
            ->andReturn(false, true);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->create('unit@test.com', 'Password123');

        $this->assertEquals($this->tokenDetails, $result);
    }

    public function testActivateAccountNotFound()
    {
        $this->authUserRepository->shouldReceive('activate')
            ->withArgs(['activation_token'])
            ->once()
            ->andReturn(false);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->activate('activation_token');

        $this->assertEquals('account-not-found', $result);
    }

    public function testActivateSuccessful()
    {
        $this->authUserRepository->shouldReceive('activate')
            ->withArgs(['activation_token'])
            ->once()
            ->andReturn(true);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->activate('activation_token');

        $this->assertEquals(true, $result);
    }

    public function testDeleteUserNotFound()
    {
        $this->setUserDataSourceGetByIdExpectation(1, null);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->delete(1, 'expired');

        $this->assertEquals('user-not-found', $result);
    }

    public function testDeleteUserNotFoundWhenDeleting()
    {
        $this->setUserDataSourceGetByIdExpectation(1, new User([]));

        $this->authUserRepository->shouldReceive('delete')
            ->withArgs([1])
            ->once()
            ->andReturn(false);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->delete(1, 'expired');

        $this->assertEquals('user-not-found', $result);
    }

    public function testDeleteSuccess()
    {
        $this->setUserDataSourceGetByIdExpectation(1, new User(['identity' => 'unit@test.com']));

        $this->authUserRepository->shouldReceive('delete')
            ->withArgs([1])
            ->once()
            ->andReturn(true);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthLogRepository($this->authLogRepository)
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $this->authLogRepository->shouldReceive('addLog')
            ->withArgs(function ($details) {
                return $details['identity_hash'] === hash('sha512', strtolower(trim('unit@test.com')))
                    && $details['type'] === 'account-deleted'
                    && $details['reason'] === 'expired'
                    && $details['loggedAt'] <= new DateTime();
            })->once();

        $result = $service->delete(1, 'expired');

        $this->assertEquals(true, $result);
    }

    /**
     * @param string $username
     * @param array $log
     */
    private function setLogDataSourceGetLogByIdentityHashExpectation(string $username, $log)
    {
        $hash = hash('sha512', strtolower(trim($username)));

        $this->authLogRepository->shouldReceive('getLogByIdentityHash')
            ->withArgs([$hash])->once()
            ->andReturn($log);
    }

    /**
     * @param int $userId
     * @param User $user
     */
    private function setUserDataSourceGetByIdExpectation(int $userId, $user)
    {
        $this->authUserRepository->shouldReceive('getById')
            ->withArgs([$userId])->once()
            ->andReturn($user);
    }

    /**
     * @param string $username
     * @param User $user
     */
    private function setUserDataSourceGetByUsernameExpectation(string $username, $user)
    {
        $this->authUserRepository->shouldReceive('getByUsername')
            ->withArgs([$username])->once()
            ->andReturn($user);
    }
}
