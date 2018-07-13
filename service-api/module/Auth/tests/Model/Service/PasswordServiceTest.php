<?php

namespace AuthTest\Model\Service;

use Auth\Model\Service\AuthenticationService;
use Application\Model\DataAccess\Mongo\Collection\User;
use Auth\Model\Service\PasswordService;
use DateTime;
use Mockery;
use Mockery\MockInterface;

class PasswordServiceTest extends ServiceTestCase
{
    /**
     * @var PasswordService
     */
    private $service;

    /**
     * @var MockInterface|AuthenticationService
     */
    private $authenticationService;

    /**
     * @var User
     */
    private $user;

    /**
     * @var array
     */
    private $tokenDetails;

    protected function setUp()
    {
        parent::setUp();

        $this->authenticationService = Mockery::mock(AuthenticationService::class);

        $this->user = new User([]);

        $this->service = new PasswordService($this->authUserCollection);

        $this->service->setAuthenticationService($this->authenticationService);
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

        $this->authUserCollection->shouldReceive('setNewPassword')
            ->withArgs(function ($userId, $passwordHash) {
                return $userId === 1 && password_verify('Password123', $passwordHash);
            })->once();

        $this->authenticationService->shouldReceive('withPassword')
            ->withArgs(['test@test.com', 'Password123', true])->once()
            ->andReturn([]);

        $result = $this->service->changePassword(1, 'valid', 'Password123');

        $this->assertEquals([], $result);
    }

    public function testGenerateTokenUserNotFound()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $result = $this->service->generateToken('unit@test.com');

        $this->assertEquals('user-not-found', $result);
    }

    public function testGenerateTokenUserNotActivated()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([
            'active' => false,
            'activation_token' => 'unit_test_activation_token'
        ]));

        $result = $this->service->generateToken('unit@test.com');

        $this->assertEquals(['activation_token' => 'unit_test_activation_token'], $result);
    }

    public function testGenerateTokenSuccess()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([
            '_id' => 1,
            'active' => true
        ]));

        $this->authUserCollection->shouldReceive('addPasswordResetToken')
            ->withArgs(function ($id, $token) {
                //Store generated token details for later validation
                $this->tokenDetails = $token;

                $expectedExpires = new DateTime('+' . (PasswordService::TOKEN_TTL - 1) . ' seconds');

                return $id === 1 && strlen($token['token']) > 20
                    && $token['expiresIn'] === PasswordService::TOKEN_TTL
                    && $token['expiresAt'] > $expectedExpires;
            })->once();

        $result = $this->service->generateToken('unit@test.com');

        $this->assertEquals($this->tokenDetails, $result);
    }

    public function testUpdatePasswordUsingTokenInvalidPassword()
    {
        $result = $this->service->updatePasswordUsingToken('token', 'invalid');

        $this->assertEquals('invalid-password', $result);
    }

    public function testUpdatePasswordUsingTokenInvalidToken()
    {
        $this->setUserDataSourceGetByResetTokenExpectation('invalid', null);

        $result = $this->service->updatePasswordUsingToken('invalid', 'Password123');

        $this->assertEquals('invalid-token', $result);
    }

    public function testUpdatePasswordUsingTokenUpdateFailed()
    {
        $this->setUserDataSourceGetByResetTokenExpectation('token', new User([]));

        $this->authUserCollection->shouldReceive('updatePasswordUsingToken')
            ->withArgs(function ($token, $passwordHash) {
                return $token === 'token' && password_verify('Password123', $passwordHash);
            })->once()->andReturn(false);

        $result = $this->service->updatePasswordUsingToken('token', 'Password123');

        $this->assertEquals(false, $result);
    }

    public function testUpdatePasswordUsingTokenUpdateSuccess()
    {
        $this->setUserDataSourceGetByResetTokenExpectation('token', new User(['_id' => 1]));

        $this->authUserCollection->shouldReceive('updatePasswordUsingToken')
            ->withArgs(function ($token, $passwordHash) {
                return $token === 'token' && password_verify('Password123', $passwordHash);
            })->once()->andReturn(true);

        $this->authUserCollection->shouldReceive('resetFailedLoginCounter')
            ->withArgs([1])->once();

        $result = $this->service->updatePasswordUsingToken('token', 'Password123');

        $this->assertEquals(true, $result);
    }
}
