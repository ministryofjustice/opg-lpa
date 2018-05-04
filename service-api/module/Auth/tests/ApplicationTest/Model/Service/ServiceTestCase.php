<?php

namespace AuthTest\Model\Service;

use Auth\Model\Service\DataAccess\LogDataSourceInterface;
use Auth\Model\Service\DataAccess\UserDataSourceInterface;
use Auth\Model\Service\DataAccess\UserInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

abstract class ServiceTestCase extends MockeryTestCase
{
    /**
     * @var MockInterface|UserDataSourceInterface
     */
    protected $userDataSource;

    /**
     * @var MockInterface|LogDataSourceInterface
     */
    protected $logDataSource;

    protected function setUp()
    {
        $this->userDataSource = Mockery::mock(UserDataSourceInterface::class);

        $this->logDataSource = Mockery::mock(LogDataSourceInterface::class);
    }

    /**
     * @param int $userId
     * @param UserInterface $user
     */
    protected function setUserDataSourceGetByIdExpectation(int $userId, $user)
    {
        $this->userDataSource->shouldReceive('getById')
            ->withArgs([$userId])->once()
            ->andReturn($user);
    }

    /**
     * @param string $username
     * @param UserInterface $user
     */
    protected function setUserDataSourceGetByUsernameExpectation(string $username, $user)
    {
        $this->userDataSource->shouldReceive('getByUsername')
            ->withArgs([$username])->once()
            ->andReturn($user);
    }

    /**
     * @param string $token
     * @param UserInterface $user
     */
    protected function setUserDataSourceGetByAuthTokenExpectation(string $token, $user)
    {
        $this->userDataSource->shouldReceive('getByAuthToken')
            ->withArgs([$token])->once()
            ->andReturn($user);
    }

    /**
     * @param string $token
     * @param UserInterface $user
     */
    protected function setUserDataSourceGetByResetTokenExpectation(string $token, $user)
    {
        $this->userDataSource->shouldReceive('getByResetToken')
            ->withArgs([$token])->once()
            ->andReturn($user);
    }

    /**
     * @param string $username
     * @param array $log
     */
    protected function setLogDataSourceGetLogByIdentityHashExpectation(string $username, $log)
    {
        $hash = hash('sha512', strtolower(trim($username)));

        $this->logDataSource->shouldReceive('getLogByIdentityHash')
            ->withArgs([$hash])->once()
            ->andReturn($log);
    }
}
