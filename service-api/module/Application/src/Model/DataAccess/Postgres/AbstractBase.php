<?php
namespace Application\Model\DataAccess\Postgres;

use Application\Logging\LoggerTrait;
use Laminas\Db\Adapter\Adapter as ZendDbAdapter;

class AbstractBase {
    use LoggerTrait;

    /**
     * Time format to use when converting DateTime to a string.
     */
    const TIME_FORMAT = 'Y-m-d\TH:i:s.uO'; // ISO8601 including microseconds

    /**
     * @var ZendDbAdapter
     */
    private $adapter;

    /**
     * @var array
     */
    private $config;

    /**
     * AbstractBase constructor.
     * @param ZendDbAdapter $adapter
     * @param array $config
     */
    public final function __construct(ZendDbAdapter $adapter, array $config)
    {
        $this->adapter = $adapter;
        $this->config = $config;
    }

    /**
     * @return ZendDbAdapter
     */
    protected function getZendDb() : ZendDbAdapter
    {
        return $this->adapter;
    }

    /**
     * Returns the global config.
     * @return array
     */
    protected function config(): array
    {
        return $this->config;
    }

}
