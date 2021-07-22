<?php
namespace Application\Model\DataAccess\Postgres;

use Application\Logging\LoggerTrait;
use Laminas\Db\Adapter\Adapter as ZendDbAdapter;
use Laminas\Db\Metadata\Source\Factory as DbMetadataFactory;
use Laminas\Db\ResultSet;
use Laminas\Db\Sql\Sql;

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
    public function __construct(ZendDbAdapter $adapter, array $config)
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

    /**
     * Returns table object for given name.
     * @return ???
     */
    protected function getTable(string $tableName)
    {
        $metadata = DbMetadataFactory::createSourceFromAdapter($this->adapter);
        return $metadata->getTable($tableName);
    }

    /**
     * Perform a raw SQL query via the adapter.
     * @return ResultSet
     */
    protected function rawQuery(string $query)
    {
        return $this->adapter->query($query, $this->adapter::QUERY_MODE_EXECUTE);
    }

    /**
     * Quote a string for use in a SQL query.
     * This returns the string with quote marks round it and escapes
     * any single quotes.
     * @return string
     */
    protected function quoteValue(string $toQuote)
    {
        return $this->adapter->getPlatform()->quoteValue($toQuote);
    }

    /**
     * Create a SQL statement ready for addition of clauses etc.
     * @return Sql
     */
    protected function createSql()
    {
        return new Sql($this->adapter);
    }
}