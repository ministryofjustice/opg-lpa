<?php

namespace AuthTest\Model\Service;

use Auth\Model\Service\AuthenticationService;
use Auth\Model\Service\DataAccess\Mongo\User;
use Auth\Model\Service\DataAccess\UserInterface;
use Auth\Model\Service\PasswordChangeService;
use Mockery;
use Mockery\MockInterface;

class PasswordChangeServiceTest extends ServiceTestCase
{
    /**
     * @var PasswordChangeService
     */
    private $service;

    /**
     * @var MockInterface|AuthenticationService
     */
    private $authenticationService;

    /**
     * @var UserInterface
     */
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->authenticationService = Mockery::mock(AuthenticationService::class);

        $this->user = new User([]);

        $this->service = new PasswordChangeService(
            $this->userDataSource,
            $this->logDataSource,
            $this->authenticationService
        );
    }

    public function testChangePasswordNullUser()
    {
        $this->setUserDataSourceGetByIdExpectation(1, null);

        $result = $this->service->changePassword(1, 'test', 'new');

        $this->assertEquals('user-not-found', $result);
    }

    public function testChangePasswordInvalidNewPassword()
    {
        $this->setUserDataSourceGetByIdExpectation(1, $this->user);

        $result = $this->service->changePassword(1, 'test', 'invalid');

        $this->assertEquals('invalid-new-password', $result);
    }

    public function testChangePasswordInvalidUserCredentials()
    {
        $this->user = new User(['password_hash' => password_hash('valid', PASSWORD_DEFAULT)]);

        $this->setUserDataSourceGetByIdExpectation(1, $this->user);

        $result = $this->service->changePassword(1, 'invalid', 'Password123');

        $this->assertEquals('invalid-user-credentials', $result);
    }

    public function testChangePasswordValid()
    {
        $this->user = new User([
            '_id' => 1,
            'identity' => 'test@test.com',
            'password_hash' => password_hash('valid', PASSWORD_DEFAULT)
        ]);

        $this->setUserDataSourceGetByIdExpectation(1, $this->user);

        $this->userDataSource->shouldReceive('setNewPassword')
            ->withArgs(function ($userId, $passwordHash) {
                return $userId === 1 && password_verify('Password123', $passwordHash);
            })->once();

        $this->authenticationService->shouldReceive('withPassword')
            ->withArgs(['test@test.com', 'Password123', true])->once()
            ->andReturn([]);

        $result = $this->service->changePassword(1, 'valid', 'Password123');

        $this->assertEquals([], $result);
    }
}
