<?php

namespace AuthTest\Model\Service;

use Application\Model\DataAccess\Repository\User\UpdatePasswordUsingTokenError;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\Password\Service as PasswordService;
use ApplicationTest\Model\Service\AbstractServiceTestCase;
use Application\Model\DataAccess\Postgres\UserModel as User;
use DateTime;
use Mockery;
use Mockery\MockInterface;

class ServiceTest extends AbstractServiceTestCase
{
    /**
     * @var MockInterface|UserRepositoryInterface
     */
    private $authUserRepository;

    /**
     * @var MockInterface|AuthenticationService
     */
    private $authenticationService;

    protected function setUp(): void
    {
        parent::setUp();

        //  Set up the services so they can be enhanced for each test
        $this->authUserRepository = Mockery::mock(UserRepositoryInterface::class);

        $this->authenticationService = Mockery::mock(AuthenticationService::class);
    }

    public function testChangePasswordNullUser()
    {
        $this->setUserDataSourceGetByIdExpectation(1, null);

        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);

        $result = $service->changePassword(1, 'test', 'new');

        $this->assertEquals('user-not-found', $result);
    }

    public function testChangePasswordInvalidNewPassword()
    {
        $user = new User([]);

        $this->setUserDataSourceGetByIdExpectation(1, $user);

        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);

        $result = $service->changePassword(1, 'test', 'invalid');

        $this->assertEquals('invalid-new-password', $result);
    }

    public function testChangePasswordInvalidUserCredentials()
    {
        $user = new User([
            'password_hash' => password_hash('valid', PASSWORD_DEFAULT)
        ]);

        $this->setUserDataSourceGetByIdExpectation(1, $user);

        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);

        $result = $service->changePassword(1, 'invalid', 'Password123');

        $this->assertEquals('invalid-user-credentials', $result);
    }

    public function testChangePasswordValid()
    {
        $user = new User([
            'id' => 1,
            'identity' => 'test@test.com',
            'password_hash' => password_hash('valid', PASSWORD_DEFAULT)
        ]);

        $this->setUserDataSourceGetByIdExpectation(1, $user);

        $this->authUserRepository->shouldReceive('setNewPassword')
            ->withArgs(function ($userId, $passwordHash) {
                return $userId === "1" && password_verify('Password123', $passwordHash);
            })->once();

        $this->authenticationService->shouldReceive('withPassword')
            ->withArgs(['test@test.com', 'Password123', true])->once()
            ->andReturn([]);

        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);

        $result = $service->changePassword(1, 'valid', 'Password123');

        $this->assertEquals([], $result);
    }

    public function testGenerateTokenUserNotFound()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);

        $result = $service->generateToken('unit@test.com');

        $this->assertEquals('user-not-found', $result);
    }

    public function testGenerateTokenUserNotActivated()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([
            'active' => false,
            'activation_token' => 'unit_test_activation_token'
        ]));

        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);

        $result = $service->generateToken('unit@test.com');

        $this->assertEquals(['activation_token' => 'unit_test_activation_token'], $result);
    }

    /**
     * Class value to use during verification below
     * @var string
     */
    private $tokenDetails;

    public function testGenerateTokenSuccess()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([
            'id' => 1,
            'active' => true
        ]));

        $checkValue = false;

        $this->authUserRepository->shouldReceive('addPasswordResetToken')
            ->withArgs(function ($id, $token) use ($checkValue) {
                //Store generated token details for later validation
                $this->tokenDetails = $token;

                $expectedExpires = new DateTime('+' . (PasswordService::TOKEN_TTL - 1) . ' seconds');

                return $id === "1" && strlen($token['token']) > 20
                    && $token['expiresIn'] === PasswordService::TOKEN_TTL
                    && $token['expiresAt'] > $expectedExpires;
            })->once();

        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);

        $result = $service->generateToken('unit@test.com');

        $this->assertEquals($this->tokenDetails, $result);
    }

    public function testGenerateTokenHashed()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit-hashed@test.com', new User([
            'id' => 1,
            'active' => true
        ]));

        $this->authUserRepository->shouldReceive('addPasswordResetToken')
            ->withArgs(function ($id, $token) {
                $expectedExpires = new DateTime('+' . (PasswordService::TOKEN_TTL - 1) . ' seconds');

                return $id === "1"
                    && $token['token'] === '22a562741b076687cb4c5efe12d5c18798aa0c46'
                    && $token['expiresIn'] === PasswordService::TOKEN_TTL
                    && $token['expiresAt'] > $expectedExpires;
            })->once();

        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);
        $service->setUseHashTokens(true);

        $result = $service->generateToken('unit-hashed@test.com');

        $this->assertEquals('22a562741b076687cb4c5efe12d5c18798aa0c46', $result['token']);
    }

    public function testUpdatePasswordUsingTokenInvalidPassword()
    {
        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);

        $result = $service->updatePasswordUsingToken('token', 'invalid');

        $this->assertEquals('invalid-password', $result);
    }

    public function testUpdatePasswordUsingTokenInvalidToken()
    {
        $this->setUserDataSourceGetByResetTokenExpectation('invalid', null);

        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);

        $result = $service->updatePasswordUsingToken('invalid', 'Password123');

        $this->assertEquals('invalid-token', $result);
    }

    public function testUpdatePasswordUsingTokenUpdateFailed()
    {
        $this->setUserDataSourceGetByResetTokenExpectation('token', new User([]));

        $this->authUserRepository->shouldReceive('updatePasswordUsingToken')
            ->withArgs(function ($token, $passwordHash) {
                return $token === 'token' && password_verify('Password123', $passwordHash);
            })
            ->once()
            ->andReturn(new UpdatePasswordUsingTokenError("error-type"));

        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);

        $result = $service->updatePasswordUsingToken('token', 'Password123');

        $this->assertEquals("error-type", $result);
    }

    public function testUpdatePasswordUsingTokenUpdateSuccess()
    {
        $this->setUserDataSourceGetByResetTokenExpectation('token', new User(['id' => 1]));

        $this->authUserRepository->shouldReceive('updatePasswordUsingToken')
            ->withArgs(function ($token, $passwordHash) {
                return $token === 'token' && password_verify('Password123', $passwordHash);
            })->once()->andReturn(null);

        $this->authUserRepository->shouldReceive('resetFailedLoginCounter')
            ->withArgs([1])->once();

        $service = new PasswordService();
        $service->setUserRepository($this->authUserRepository);
        $service->setAuthenticationService($this->authenticationService);

        $result = $service->updatePasswordUsingToken('token', 'Password123');

        $this->assertEquals(null, $result);
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

    /**
     * @param string $token
     * @param $user
     */
    private function setUserDataSourceGetByResetTokenExpectation(string $token, $user)
    {
        $this->authUserRepository->shouldReceive('getByResetToken')
            ->withArgs([$token])->once()
            ->andReturn($user);
    }
}
