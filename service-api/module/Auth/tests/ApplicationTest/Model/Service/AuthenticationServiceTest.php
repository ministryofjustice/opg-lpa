<?php

namespace AuthTest\Model\Service;

use Auth\Model\Service\AuthenticationService;
use Auth\Model\Service\DataAccess\Mongo\User;
use DateInterval;
use DateTime;

/**
 * I'm not entirely happy with many of these tests as they use DateTimes and are potentially non-deterministic.
 * Normally I would mock the datetime generation in the tested class but it's not the pattern at the OPG
 *
 * Class AuthenticationServiceTest
 * @package AuthTest\Model\Service
 */
class AuthenticationServiceTest extends ServiceTestCase
{
    /**
     * @var AuthenticationService
     */
    private $service;

    /**
     * @var array
     */
    private $tokenDetails;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new AuthenticationService($this->userDataSource, $this->logDataSource);
    }

    public function testWithPasswordMissingCredentials()
    {
        $result = $this->service->withPassword(null, null, false);

        $this->assertEquals('missing-credentials', $result);
    }

    public function testWithPasswordNullUser()
    {
        $this->setUserDataSourceGetByUsernameExpectation('not@found.com', null);

        $result = $this->service->withPassword('not@found.com', 'valid', false);

        $this->assertEquals('user-not-found', $result);
    }

    public function testWithPasswordNotActivated()
    {
        $this->setUserDataSourceGetByUsernameExpectation('not@active.com', new User(['active' => false]));

        $result = $this->service->withPassword('not@active.com', 'valid', false);

        $this->assertEquals('account-not-active', $result);
    }

    public function testWithPasswordMaxLoginAttempts()
    {
        $this->setUserDataSourceGetByUsernameExpectation('max@logins.com', new User([
            'active' => true,
            'failed_login_attempts' => AuthenticationService::MAX_ALLOWED_LOGIN_ATTEMPTS,
            'last_failed_login' => new DateTime()
        ]));

        $result = $this->service->withPassword('max@logins.com', 'valid', false);

        $this->assertEquals('account-locked/max-login-attempts', $result);
    }

    public function testWithPasswordMaxLoginAttemptsResetInvalidCredentials()
    {
        $this->setUserDataSourceGetByUsernameExpectation('max@logins.com', new User([
            '_id' => 1,
            'active' => true,
            'failed_login_attempts' => AuthenticationService::MAX_ALLOWED_LOGIN_ATTEMPTS,
            'last_failed_login' => (new DateTime())
                ->sub(new DateInterval('PT' . (AuthenticationService::ACCOUNT_LOCK_TIME + 1) . 'S'))
        ]));

        $this->userDataSource->shouldReceive('resetFailedLoginCounter')
            ->withArgs([1])->once();

        $this->userDataSource->shouldReceive('incrementFailedLoginCounter')
            ->withArgs([1])->once();

        $result = $this->service->withPassword('max@logins.com', 'valid', false);

        $this->assertEquals('invalid-user-credentials', $result);
    }

    public function testWithPasswordInvalidCredentialsMaxLoginAttempts()
    {
        $this->setUserDataSourceGetByUsernameExpectation('max@logins.com', new User([
            '_id' => 1,
            'active' => true,
            'failed_login_attempts' => AuthenticationService::MAX_ALLOWED_LOGIN_ATTEMPTS - 1
        ]));

        $this->userDataSource->shouldReceive('incrementFailedLoginCounter')
            ->withArgs([1])->once();

        $result = $this->service->withPassword('max@logins.com', 'valid', false);

        $this->assertEquals('invalid-user-credentials/account-locked', $result);
    }

    public function testWithPasswordValidCredentialsResetLoginAttempts()
    {
        $today = new DateTime('today');

        $this->setUserDataSourceGetByUsernameExpectation('test@test.com', new User([
            '_id' => 1,
            'identity' => 'test@test.com',
            'active' => true,
            'failed_login_attempts' => AuthenticationService::MAX_ALLOWED_LOGIN_ATTEMPTS - 1,
            'password_hash' => password_hash('valid', PASSWORD_DEFAULT),
            'last_login' => $today
        ]));

        $this->userDataSource->shouldReceive('updateLastLoginTime')
            ->withArgs([1])->once();

        $this->userDataSource->shouldReceive('resetFailedLoginCounter')
            ->withArgs([1])->once();

        $result = $this->service->withPassword('test@test.com', 'valid', false);

        $this->assertEquals([
            'userId' => 1,
            'username' => 'test@test.com',
            'last_login' => $today,
            'inactivityFlagsCleared' => false,
        ], $result);
    }

    public function testWithPasswordValidCredentialsCreateToken()
    {
        $today = new DateTime('today');

        $this->setUserDataSourceGetByUsernameExpectation('test@test.com', new User([
            '_id' => 1,
            'identity' => 'test@test.com',
            'active' => true,
            'password_hash' => password_hash('valid', PASSWORD_DEFAULT),
            'last_login' => $today
        ]));

        $this->userDataSource->shouldReceive('updateLastLoginTime')
            ->withArgs([1])->once();

        $this->userDataSource->shouldReceive('setAuthToken')
            ->withArgs(function ($userId, $expires, $authToken) {
                //Store generated token details for later validation
                $this->tokenDetails = [
                    'token' => $authToken,
                    'expiresIn' => AuthenticationService::TOKEN_TTL,
                    'expiresAt' => $expires
                ];

                $expectedExpires = new DateTime('+' . (AuthenticationService::TOKEN_TTL - 1) . ' seconds');

                return $userId === 1 && $expires > $expectedExpires && strlen($authToken) > 40;
            })->once()
            ->andReturn(true);

        $result = $this->service->withPassword('test@test.com', 'valid', true);

        $this->assertEquals([
            'userId' => 1,
            'username' => 'test@test.com',
            'last_login' => $today,
            'inactivityFlagsCleared' => false,
        ] + $this->tokenDetails, $result);
    }

    public function testWithTokenNullUser()
    {
        $this->setUserDataSourceGetByAuthTokenExpectation('token', null);

        $result = $this->service->withToken('token', false);

        $this->assertEquals('invalid-token', $result);
    }

    public function testWithTokenInvalidToken()
    {
        $this->setUserDataSourceGetByAuthTokenExpectation('token', new User([]));

        $result = $this->service->withToken('token', false);

        $this->assertEquals('invalid-token', $result);
    }

    public function testWithTokenTokenExpired()
    {
        $this->setUserDataSourceGetByAuthTokenExpectation('expired', new User([
            'auth_token' => [
                'expiresAt' => new DateTime('-1 seconds')
            ]
        ]));

        $result = $this->service->withToken('expired', false);

        $this->assertEquals('token-has-expired', $result);
    }

    public function testWithTokenNoUpdateBoolean()
    {
        $today = new DateTime('today');
        $expiresAt = new DateTime('+1 seconds');

        $this->setUserDataSourceGetByAuthTokenExpectation('valid', new User([
            '_id' => 1,
            'identity' => 'test@test.com',
            'last_login' => $today,
            'auth_token' => [
                'token' => 'valid',
                'expiresAt' => $expiresAt,
                'updatedAt' => new DateTime('-6 seconds')
            ]
        ]));

        $result = $this->service->withToken('valid', false);

        $this->assertEquals([
            'token' => 'valid',
            'userId' => 1,
            'username' => 'test@test.com',
            'last_login' => $today,
            'expiresIn' => 1,
            'expiresAt' => $expiresAt
        ], $result);
    }

    public function testWithTokenNoUpdateLastUpdated()
    {
        $today = new DateTime('today');
        $expiresAt = new DateTime('+1 seconds');

        $this->setUserDataSourceGetByAuthTokenExpectation('valid', new User([
            '_id' => 1,
            'identity' => 'test@test.com',
            'last_login' => $today,
            'auth_token' => [
                'token' => 'valid',
                'expiresAt' => $expiresAt,
                'updatedAt' => new DateTime('-5 seconds')
            ]
        ]));

        $result = $this->service->withToken('valid', true);

        $this->assertEquals([
            'token' => 'valid',
            'userId' => 1,
            'username' => 'test@test.com',
            'last_login' => $today,
            'expiresIn' => 1,
            'expiresAt' => $expiresAt
        ], $result);
    }

    public function testWithTokenUpdate()
    {
        $today = new DateTime('today');
        $expiresAt = new DateTime('+1 seconds');

        $this->setUserDataSourceGetByAuthTokenExpectation('valid', new User([
            '_id' => 1,
            'identity' => 'test@test.com',
            'last_login' => $today,
            'auth_token' => [
                'token' => 'valid',
                'expiresAt' => $expiresAt,
                'updatedAt' => new DateTime('-6 seconds')
            ]
        ]));

        $this->userDataSource->shouldReceive('extendAuthToken')
            ->withArgs(function ($userId, $expires) {
                //Store generated token details for later validation
                $this->tokenDetails = [
                    'expiresIn' => AuthenticationService::TOKEN_TTL,
                    'expiresAt' => $expires
                ];

                $expectedExpires = new DateTime('+' . (AuthenticationService::TOKEN_TTL - 1) . ' seconds');

                return $userId === 1 && $expires > $expectedExpires;
            })->once()
            ->andReturn(true);

        $result = $this->service->withToken('valid', true);

        $this->assertEquals([
            'token' => 'valid',
            'userId' => 1,
            'username' => 'test@test.com',
            'last_login' => $today
        ] + $this->tokenDetails, $result);
    }

    public function testDeleteToken()
    {
        $this->userDataSource->shouldReceive('removeAuthToken')
            ->withArgs(['token'])->once();

        $result = $this->service->deleteToken('token');

        $this->assertNull($result);
    }
}
