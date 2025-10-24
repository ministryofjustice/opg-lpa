<?php

namespace ApplicationTest\Model\Service\Authentication;

use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\DataAccess\Postgres\UserModel as User;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use DateInterval;
use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class ServiceTest extends MockeryTestCase
{
    public const TIME_FORMAT = 'Y-m-d\TH:i:s.uO';

    /**
     * @var MockInterface|UserRepositoryInterface
     */
    private $authUserRepository;

    protected function setUp(): void
    {
        parent::setUp();

        //  Set up the services so they can be enhanced for each test
        $this->authUserRepository = Mockery::mock(UserRepositoryInterface::class);
    }

    public function testWithPasswordMissingCredentials()
    {
        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withPassword(null, null, false);

        $this->assertEquals('missing-credentials', $result);
    }

    public function testWithPasswordNullUser()
    {
        $this->setUserDataSourceGetByUsernameExpectation('not@found.com', null);

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withPassword('not@found.com', 'valid', false);

        $this->assertEquals('user-not-found', $result);
    }

    public function testWithPasswordNotActivated()
    {
        $this->setUserDataSourceGetByUsernameExpectation('not@active.com', new User(['active' => false]));

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withPassword('not@active.com', 'valid', false);

        $this->assertEquals('account-not-active', $result);
    }

    public function testWithPasswordMaxLoginAttempts()
    {
        $this->setUserDataSourceGetByUsernameExpectation('max@logins.com', new User([
            'active' => true,
            'failed_login_attempts' => AuthenticationService::MAX_ALLOWED_LOGIN_ATTEMPTS,
            'last_failed_login' => new DateTime()
        ]));

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withPassword('max@logins.com', 'valid', false);

        $this->assertEquals('account-locked/max-login-attempts', $result);
    }

    public function testWithPasswordMaxLoginAttemptsResetInvalidCredentials()
    {
        $this->setUserDataSourceGetByUsernameExpectation('max@logins.com', new User([
            'id' => 1,
            'active' => true,
            'failed_login_attempts' => AuthenticationService::MAX_ALLOWED_LOGIN_ATTEMPTS,
            'last_failed_login' => (new DateTime())
                ->sub(new DateInterval('PT' . (AuthenticationService::ACCOUNT_LOCK_TIME + 1) . 'S')),
                'password_hash' => password_hash('actual_password', PASSWORD_DEFAULT),
        ]));

        $this->authUserRepository->shouldReceive('resetFailedLoginCounter')
            ->withArgs([1])->once();

        $this->authUserRepository->shouldReceive('incrementFailedLoginCounter')
            ->withArgs([1])->once();

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withPassword('max@logins.com', 'valid', false);

        $this->assertEquals('invalid-user-credentials', $result);
    }

    public function testWithPasswordInvalidCredentialsMaxLoginAttempts()
    {
        $this->setUserDataSourceGetByUsernameExpectation('max@logins.com', new User([
            'id' => 1,
            'active' => true,
            'failed_login_attempts' => AuthenticationService::MAX_ALLOWED_LOGIN_ATTEMPTS - 1,
            'password_hash' => password_hash('actual_password', PASSWORD_DEFAULT),
        ]));

        $this->authUserRepository->shouldReceive('incrementFailedLoginCounter')
            ->withArgs([1])->once();

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withPassword('max@logins.com', 'valid', false);

        $this->assertEquals('invalid-user-credentials/account-locked', $result);
    }

    public function testWithPasswordValidCredentialsResetLoginAttempts()
    {
        $today = new DateTime('today');

        $this->setUserDataSourceGetByUsernameExpectation('test@test.com', new User([
            'id' => 1,
            'identity' => 'test@test.com',
            'active' => true,
            'failed_login_attempts' => AuthenticationService::MAX_ALLOWED_LOGIN_ATTEMPTS - 1,
            'password_hash' => password_hash('valid', PASSWORD_DEFAULT),
            'last_login' => $today
        ]));

        $this->authUserRepository->shouldReceive('updateLastLoginTime')
            ->withArgs([1])->once();

        $this->authUserRepository->shouldReceive('resetFailedLoginCounter')
            ->withArgs([1])->once();

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withPassword('test@test.com', 'valid', false);

        $this->assertEquals([
            'userId' => 1,
            'username' => 'test@test.com',
            'last_login' => $today,
            'inactivityFlagsCleared' => false,
        ], $result);
    }

    /**
     * Class value to use during verification below
     * @var string
     */
    private $tokenDetails;

    public function testWithPasswordValidCredentialsCreateToken()
    {
        $today = new DateTime('today');

        $this->setUserDataSourceGetByUsernameExpectation('test@test.com', new User([
            'id' => 1,
            'identity' => 'test@test.com',
            'active' => true,
            'password_hash' => password_hash('valid', PASSWORD_DEFAULT),
            'last_login' => $today
        ]));

        $this->authUserRepository->shouldReceive('updateLastLoginTime')
                                 ->withArgs([1])->once();

        //$this>shouldReceive('make_token')->once();

        $this->authUserRepository->shouldReceive('setAuthToken')
            ->withArgs(function ($userId, $expires, $authToken) {
                //Store generated token details for later validation
                $this->tokenDetails = [
                    'token' => $authToken,
                    'expiresIn' => AuthenticationService::TOKEN_TTL,
                    'expiresAt' => $expires
                ];

                $expectedExpires = new DateTime('+' . (AuthenticationService::TOKEN_TTL - 1) . ' seconds');
                if ($userId !== "1") {
                    printf("user id %s is not 1 \n", $userId);
                    return false;
                }

                if (! ($expires > $expectedExpires)) {
                    print("bad expiry date \n");
                    return false;
                }

                if (!  (strlen($authToken) > 40)) {
                    printf("token %s too short \n", $authToken);
                    return false;
                }
                return true;
            })->once()
            ->andReturn(true);

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withPassword('test@test.com', 'valid', true);

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

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withToken('token', false);

        $this->assertEquals('invalid-token', $result);
    }

    public function testWithTokenInvalidToken()
    {
        $this->setUserDataSourceGetByAuthTokenExpectation('token', new User([]));

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withToken('token', false);

        $this->assertEquals('invalid-token', $result);
    }

    public function testWithTokenTokenExpired()
    {
        $this->setUserDataSourceGetByAuthTokenExpectation('expired', new User([
            'auth_token' => json_encode([
                'expiresAt' => (new DateTime('-1 seconds'))->format(self::TIME_FORMAT)
            ])
        ]));

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withToken('expired', false);

        $this->assertEquals('token-has-expired', $result);
    }

    public function testWithTokenNoUpdateBoolean()
    {
        $today = new DateTime('today');
        $expiresAt = new DateTime('+1 seconds');

        $this->setUserDataSourceGetByAuthTokenExpectation('valid', new User([
            'id' => 1,
            'identity' => 'test@test.com',
            'last_login' => $today,
            'auth_token' => json_encode([
                'token' => 'valid',
                'expiresAt' => $expiresAt->format(self::TIME_FORMAT),
                'updatedAt' => (new DateTime('-6 seconds'))->format(self::TIME_FORMAT)
            ])
        ]));

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withToken('valid', false);

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
            'id' => '1',
            'identity' => 'test@test.com',
            'last_login' => $today->format(self::TIME_FORMAT),
            'auth_token' => json_encode([
                'token' => 'valid',
                'expiresAt' => $expiresAt->format(self::TIME_FORMAT),
                'updatedAt' => (new DateTime('-2 seconds'))->format(self::TIME_FORMAT)
            ])
        ]));

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withToken('valid', true);

        $this->assertEquals([
            'token' => 'valid',
            'userId' => '1',
            'username' => 'test@test.com',
            'last_login' => $today,
            'expiresIn' => 1,
            'expiresAt' => $expiresAt
        ], $result);
    }

    public function testWithTokenUpdate()
    {
        $today = (new DateTime('today'));
        $expiresAt = (new DateTime('+1 seconds'))->format(self::TIME_FORMAT);

        $this->setUserDataSourceGetByAuthTokenExpectation('valid', new User([
            'id' => '1',
            'identity' => 'test@test.com',
            'last_login' => $today->format(self::TIME_FORMAT),
            'auth_token' => json_encode([
                'token' => 'valid',
                'expiresAt' => $expiresAt,
                'updatedAt' => (new DateTime('-6 seconds'))->format(self::TIME_FORMAT)
            ])
        ]));

        $this->authUserRepository->shouldReceive('updateAuthTokenExpiry')
            ->withArgs(function ($userId, $expires) {
                //Store generated token details for later validation
                $this->tokenDetails = [
                    'expiresIn' => AuthenticationService::TOKEN_TTL,
                    'expiresAt' => $expires
                ];

                $expectedExpires = new DateTime('+' . (AuthenticationService::TOKEN_TTL - 1) . ' seconds');

                return $userId === "1" && $expires > $expectedExpires;
            })->once()
            ->andReturn(true);

        $service = new AuthenticationService();
        $service->setUserRepository($this->authUserRepository);

        $result = $service->withToken('valid', true);

        $this->assertEquals([
            'token' => 'valid',
            'userId' => '1',
            'username' => 'test@test.com',
            'last_login' => $today
        ] + $this->tokenDetails, $result);
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

    /**
     * @param string $token
     * @param User $user
     */
    private function setUserDataSourceGetByAuthTokenExpectation(string $token, $user)
    {
        $this->authUserRepository->shouldReceive('getByAuthToken')
            ->withArgs([$token])->once()
            ->andReturn($user);
    }
}
