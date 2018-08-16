<?php

namespace ApplicationTest\Model\Service\Email;

use Application\Model\DataAccess\Repository\Auth\UserRepositoryInterface;
use Application\Model\DataAccess\Repository\Auth\UpdateEmailUsingTokenResponse;
use Application\Model\DataAccess\Mongo\Collection\User;
use Application\Model\Service\Email\Service as EmailUpdateService;
use ApplicationTest\Model\Service\AbstractServiceTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;

class ServiceTest extends AbstractServiceTest
{
    /**
     * @var MockInterface|UserRepositoryInterface
     */
    private $authUserRepository;

    protected function setUp()
    {
        parent::setUp();

        //  Set up the services so they can be enhanced for each test
        $this->authUserRepository = Mockery::mock(UserRepositoryInterface::class);
    }

    public function testGenerateTokenInvalidEmail()
    {
        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->generateToken(1, 'invalid');

        $this->assertEquals('invalid-email', $result);
    }

    public function testGenerateTokenUsernameSameAsCurrent()
    {
        $this->setUserDataSourceGetByIdExpectation(1, new User(['_id' => 1, 'identity' => 'unit@test.com']));

        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([
            '_id' => 1,
            'identity' => 'unit@test.com'
        ]));

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->generateToken(1, 'unit@test.com');

        $this->assertEquals('username-same-as-current', $result);
    }

    public function testGenerateTokenUsernameAlreadyExists()
    {
        $this->setUserDataSourceGetByIdExpectation(1, new User(['_id' => 1, 'identity' => 'old@test.com']));

        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([
            '_id' => 2,
            'identity' => 'unit@test.com'
        ]));

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->generateToken(1, 'unit@test.com');

        $this->assertEquals('username-already-exists', $result);
    }

    public function testGenerateTokenUserNotFound()
    {
        $this->setUserDataSourceGetByIdExpectation(1, null);

        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->generateToken(1, 'unit@test.com');

        $this->assertEquals('user-not-found', $result);
    }

    /**
     * Class value to use during verification below
     * @var string
     */
    private $tokenDetails;

    public function testGenerateTokenSuccess()
    {
        $this->setUserDataSourceGetByIdExpectation(1, new User(['_id' => 1, 'identity' => 'old@test.com']));

        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $this->authUserRepository->shouldReceive('addEmailUpdateTokenAndNewEmail')
            ->withArgs(function ($id, $token, $newEmail) {
                //Store generated token details for later validation
                $this->tokenDetails = $token;

                $expectedExpires = new DateTime('+' . (EmailUpdateService::TOKEN_TTL - 1) . ' seconds');

                return $id === "1" && strlen($token['token']) > 20
                    && $token['expiresIn'] === EmailUpdateService::TOKEN_TTL && $token['expiresAt'] > $expectedExpires
                    && $newEmail === 'unit@test.com';
            })
            ->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->generateToken(1, 'unit@test.com');

        $this->assertEquals($this->tokenDetails, $result);
    }

    public function testUpdateEmailUsingToken()
    {
        $this->authUserRepository->shouldReceive('updateEmailUsingToken')->withArgs(['token'])->once();

        $serviceBuilder = new ServiceBuilder();
        $service = $serviceBuilder
            ->withAuthUserRepository($this->authUserRepository)
            ->build();

        $result = $service->updateEmailUsingToken('token');


        $this->assertInstanceOf(UpdateEmailUsingTokenResponse::class, $result);
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
