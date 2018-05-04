<?php

namespace Application\Model\Service;

use Application\Model\Service\DataAccess\LogDataSourceInterface;
use Application\Model\Service\DataAccess\UserDataSourceInterface;

abstract class AbstractService
{
    /**
     * @var UserDataSourceInterface
     */
    private $userDataSource;

    /**
     * @var LogDataSourceInterface
     */
    private $logDataSource;

    public function __construct(UserDataSourceInterface $userDataSource, LogDataSourceInterface $logDataSource)
    {
        $this->userDataSource = $userDataSource;
        $this->logDataSource = $logDataSource;
    }

    protected function getUserDataSource(): UserDataSourceInterface
    {
        return $this->userDataSource;
    }

    protected function getLogDataSource(): LogDataSourceInterface
    {
        return $this->logDataSource;
    }
}
