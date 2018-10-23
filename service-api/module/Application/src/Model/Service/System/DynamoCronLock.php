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
     * The namespace to prefix keys with.
     *
     * @var string
     */
    private $keyPrefix;

    /**
     * @param array $config
     * @param string $keyPrefix
     */
    public function __construct(array $config, $keyPrefix = 'default')
    {
        $this->config = $config;
        $this->keyPrefix = $keyPrefix;
    }

    /**
     * Get the lock for a period of time (default 60 minutes)
     *
     * @param $name
     * @param int $allowedSecondsSinceLastRun
     * @return bool
     */
    public function getLock($name, $allowedSecondsSinceLastRun = 60 * 60)
    {
        //  Create the command to execute
        $command = 'bin/lock acquire ';
        $command .= sprintf('--table %s ', $this->config['settings']['table_name']);
        $command .= sprintf('--name "%s/%s" ', $this->keyPrefix, $name);
        $command .= sprintf('--ttl %s ', $allowedSecondsSinceLastRun);
        $command .= sprintf('--endpoint %s ', $this->config['client']['endpoint']);
        $command .= sprintf('--version %s ', $this->config['client']['version']);
        $command .= sprintf('--region %s ', $this->config['client']['region']);

        //  Initialise the return value
        $output = [];
        $rtnValue = -1;

        exec($command, $output, $rtnValue);

        //  Log an appropriate message
        if ($rtnValue === 0) {
            $this->getLogger()->info(sprintf('This node got the %s cron lock for %s', $name, $name));

            return true;
        }

        $this->getLogger()->info(sprintf('This node did not get the %s cron lock for %s', $name, $name));

        return false;
    }
}
