<?php

namespace ApplicationTest\Model\Service;

use Application\Model\Service\DataAccess\Mongo\User;
use Application\Model\Service\UserManagementService;
use DateTime;
use MongoDB\BSON\UTCDateTime;

class UserManagementServiceTest extends ServiceTestCase
{
    /**
     * @var UserManagementService
     */
    private $service;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new UserManagementService($this->userDataSource, $this->logDataSource);
    }

    public function testGetUserNotFound()
    {
        $this->setUserDataSourceGetByIdExpectation(1, null);

        $result = $this->service->get(1);

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

        $result = $this->service->get(1);

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

        $result = $this->service->getByUsername('unit@test.com');

        $this->assertEquals(false, $result);
    }

    public function testGetByUsernameNullDeleted()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $this->setLogDataSourceGetLogByIdentityHashExpectation('unit@test.com', [
            'loggedAt' => new UTCDateTime(new DateTime('2018-01-08 09:10:11')),
            'reason' => 'expired'
        ]);

        $result = $this->service->getByUsername('unit@test.com');

        $this->assertEquals([
            'isDeleted' => true,
            'deletedAt' => new DateTime('2018-01-08 09:10:11'),
            'reason' => 'expired'
        ], $result);
    }

    public function testGetByUsernameSuccess()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User(['_id' => 1]));

        $result = $this->service->getByUsername('unit@test.com');

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

    public function testDeleteUserNotFound()
    {
        $this->setUserDataSourceGetByIdExpectation(1, null);

        $result = $this->service->delete(1, 'expired');

        $this->assertEquals('user-not-found', $result);
    }

    public function testDeleteUserNotFoundWhenDeleting()
    {
        $this->setUserDataSourceGetByIdExpectation(1, new User([]));

        $this->userDataSource->shouldReceive('delete')
            ->withArgs([1])->once()
            ->andReturn(false);

        $result = $this->service->delete(1, 'expired');

        $this->assertEquals('user-not-found', $result);
    }

    public function testDeleteSuccess()
    {
        $this->setUserDataSourceGetByIdExpectation(1, new User(['identity' => 'unit@test.com']));

        $this->userDataSource->shouldReceive('delete')
            ->withArgs([1])->once()
            ->andReturn(true);

        $this->logDataSource->shouldReceive('addLog')
            ->withArgs(function ($details) {
                return $details['identity_hash'] === hash('sha512', strtolower(trim('unit@test.com')))
                    && $details['type'] === 'account-deleted'
                    && $details['reason'] === 'expired'
                    && $details['loggedAt'] <= new DateTime();
            })->once();

        $result = $this->service->delete(1, 'expired');

        $this->assertEquals(true, $result);
    }
}
