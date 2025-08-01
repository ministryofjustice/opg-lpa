<?php

namespace Application\Model\DataAccess\Postgres;

use MakeShared\Logging\LoggerTrait;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Psr\Log\LoggerAwareInterface;

class AbstractBase implements LoggerAwareInterface
{
    use LoggerTrait;

    /**
     * Wrapper around db adapter and SQL generation.
     * @var DbWrapper
     */
    protected $dbWrapper;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * Constructor.
     * @param DbWrapper $dbWrapper
     * @param array $config
     */
    final public function __construct(DbWrapper $dbWrapper, array $config = [])
    {
        $this->dbWrapper = $dbWrapper;
        $this->config = $config;
    }

    public function config()
    {
        return $this->config;
    }
}
