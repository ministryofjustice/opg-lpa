<?php

namespace AuthTest\Model\Service;

use Auth\Model\DataAccess\Mongo\User;
use Auth\Model\Service\PasswordResetService;
use DateTime;

class PasswordResetServiceTest extends ServiceTestCase
{
    /**
     * @var PasswordResetService
     */
    private $service;

    /**
     * @var array
     */
    private $tokenDetails;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new PasswordResetService($this->userDataSource, $this->logDataSource);
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

        $this->userDataSource->shouldReceive('addPasswordResetToken')
            ->withArgs(function ($id, $token) {
                //Store generated token details for later validation
                $this->tokenDetails = $token;

                $expectedExpires = new DateTime('+' . (PasswordResetService::TOKEN_TTL - 1) . ' seconds');

                return $id === 1 && strlen($token['token']) > 20
                    && $token['expiresIn'] === PasswordResetService::TOKEN_TTL
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

        $this->userDataSource->shouldReceive('updatePasswordUsingToken')
            ->withArgs(function ($token, $passwordHash) {
                return $token === 'token' && password_verify('Password123', $passwordHash);
            })->once()->andReturn(false);

        $result = $this->service->updatePasswordUsingToken('token', 'Password123');

        $this->assertEquals(false, $result);
    }

    public function testUpdatePasswordUsingTokenUpdateSuccess()
    {
        $this->setUserDataSourceGetByResetTokenExpectation('token', new User(['_id' => 1]));

        $this->userDataSource->shouldReceive('updatePasswordUsingToken')
            ->withArgs(function ($token, $passwordHash) {
                return $token === 'token' && password_verify('Password123', $passwordHash);
            })->once()->andReturn(true);

        $this->userDataSource->shouldReceive('resetFailedLoginCounter')
            ->withArgs([1])->once();

        $result = $this->service->updatePasswordUsingToken('token', 'Password123');

        $this->assertEquals(true, $result);
    }
}
