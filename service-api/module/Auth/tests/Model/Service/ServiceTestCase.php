<?php

namespace AuthTest\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use Application\Model\DataAccess\Mongo\Collection\User;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

abstract class ServiceTestCase extends MockeryTestCase
{
    /**
     * @var MockInterface|AuthUserCollection
     */
    protected $authUserCollection;

    protected function setUp()
    {
        $this->authUserCollection = Mockery::mock(AuthUserCollection::class);
    }

    /**
     * @param int $userId
     * @param User $user
     */
    protected function setUserDataSourceGetByIdExpectation(int $userId, $user)
    {
        $this->authUserCollection->shouldReceive('getById')
            ->withArgs([$userId])->once()
            ->andReturn($user);
    }

    /**
     * @param string $username
     * @param User $user
     */
    protected function setUserDataSourceGetByUsernameExpectation(string $username, $user)
    {
        $this->authUserCollection->shouldReceive('getByUsername')
            ->withArgs([$username])->once()
            ->andReturn($user);
    }

    /**
     * @param string $token
     * @param User $user
     */
    protected function setUserDataSourceGetByAuthTokenExpectation(string $token, $user)
    {
        $this->authUserCollection->shouldReceive('getByAuthToken')
            ->withArgs([$token])->once()
            ->andReturn($user);
    }

    /**
     * @param string $token
     * @param User $user
     */
    protected function setUserDataSourceGetByResetTokenExpectation(string $token, $user)
    {
        $this->authUserCollection->shouldReceive('getByResetToken')
            ->withArgs([$token])->once()
            ->andReturn($user);
    }
}
