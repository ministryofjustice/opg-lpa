<?php
namespace Application\Model\DataAccess\Postgres;

use Zend\Db\Adapter\Adapter as ZendDbAdapter;

class AbstractBase {

    /**
     * Time format to use when converting DateTime to a string.
     */
    const TIME_FORMAT = 'Y-m-d\TH:i:s.uO'; // ISO8601 including microseconds

    /**
     * @var ZendDbAdapter
     */
    private $adapter;

    /**
     * AbstractBase constructor.
     * @param ZendDbAdapter $adapter
     */
    public final function __construct(ZendDbAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @return ZendDbAdapter
     */
    protected function getZendDb() : ZendDbAdapter
    {
        return $this->adapter;
    }

}
