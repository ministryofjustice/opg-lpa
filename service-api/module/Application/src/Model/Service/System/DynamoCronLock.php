<?php

namespace Application\Model\Service\System;

use Opg\Lpa\Logger\LoggerTrait;

class DynamoCronLock
{
    use LoggerTrait;

    /**
     * @var array
     */
    private $config;

    /**
     * Prefix for the lock name
     *
     * @var string
     */
    private $namePrefix;

    /**
     * @param array $config
     * @param string $namePrefix
     */
    public function __construct(array $config, $namePrefix = 'default')
    {
        $this->config = $config;
        $this->namePrefix = $namePrefix;
    }

    /**
     * Get the lock for a period of time (default 60 minutes)
     *
     * @param $lockName
     * @param int $allowedSecondsSinceLastRun
     * @return bool
     */
    public function getLock($lockName, $allowedSecondsSinceLastRun = 60 * 60)
    {
        //  Create the command to execute
        $command = 'bin/lock acquire ';
        $command .= sprintf('--name "%s/%s" ', $this->namePrefix, $lockName);
        $command .= sprintf('--table %s ', $this->config['settings']['table_name']);
        $command .= sprintf('--ttl %s ', $allowedSecondsSinceLastRun);

        //  Add any optional values
        $paramNames = [
            'endpoint',
            'version',
            'region',
        ];

        foreach ($paramNames as $paramName) {
            if (isset($this->config['client'][$paramName]) && !empty($this->config['client'][$paramName])) {
                $command .= sprintf('--%s %s ', $paramName, $this->config['client'][$paramName]);
            }
        }

        //  Initialise the return value
        $output = [];
        $rtnValue = -1;

        exec($command, $output, $rtnValue);

        //  Log an appropriate message
        if ($rtnValue === 0) {
            $this->getLogger()->info(sprintf('This node got the %s cron lock for %s', $lockName, $lockName));

            return true;
        }

        $this->getLogger()->info(sprintf('This node did not get the %s cron lock for %s', $lockName, $lockName));

        return false;
    }
}
