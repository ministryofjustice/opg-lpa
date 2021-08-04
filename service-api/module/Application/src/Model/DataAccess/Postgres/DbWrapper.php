<?php
namespace Application\Model\DataAccess\Postgres;

use Application\Logging\LoggerTrait;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Metadata\Object\TableObject;
use Laminas\Db\Metadata\Source\Factory as DbMetadataFactory;
use Laminas\Db\ResultSet;
use Laminas\Db\Sql\Predicate\Expression;
use Laminas\Db\Sql\Sql;

/**
 * Long term plan is to move all subclasses of AbstractBase to instead
 * have a DbWrapper instance injected into them, to make them testable.
 * See DataFactory.php which shows the pattern for creating instances
 * of *Data classes using DbWrapper.
 */
class DbWrapper {
    /**
     * Time format to use when converting DateTime to a string.
     */
    const TIME_FORMAT = 'Y-m-d\TH:i:s.uO'; // ISO8601 including microseconds

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * Constructor.
     * @param Adapter $adapter
     * @param string $tableName
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Returns table object for given name.
     * @param string $tableName Name of table to retrieve metadata for
     * @return TableObject
     */
    public function getTable(string $tableName) : TableObject
    {
        $metadata = DbMetadataFactory::createSourceFromAdapter($this->adapter);
        return $metadata->getTable($tableName);
    }

    /**
     * Perform a raw SQL query via the adapter.
     * @param string $query Raw SQL string to execute on adapter
     * @return ResultSet
     */
    public function rawQuery(string $query) : ResultSet
    {
        return $this->adapter->query($query, $this->adapter::QUERY_MODE_EXECUTE);
    }

    /**
     * Quote a string for use in a SQL query.
     * This returns the string with quote marks round it and escapes
     * any single quotes.
     * @param string $toQuote String to be quoted
     * @return string
     */
    public function quoteValue(string $toQuote) : string
    {
        return $this->adapter->getPlatform()->quoteValue($toQuote);
    }

    /**
     * Create a SQL statement ready for addition of clauses etc.
     * @return Sql
     */
    public function createSql() : Sql
    {
        return new Sql($this->adapter);
    }

    /**
     * Perform a SQL SELECT against the db.
     *
     * @param string $tableName Name of table to select against
     * @param array $criteria Added to the WHERE clause; the "search" key is escaped
     * and used for a regex match if present
     * @param array $options Used to set columns, LIMIT, OFFSET and SORT
     *
     * For sorting:
     * $options = [
     *     'sort' => [
     *         'column1' => 1,   # ASC sort
     *         'column2' => 0    # DESC sort
     *     ]
     * ]
     *
     * @return Result
     */
    public function select(string $tableName, array $criteria, array $options=[]) : Result
    {
        $sql = $this->createSql();

        $select = $sql->select($tableName);

        if (isset($criteria['search'])) {
            $quoted = $this->quoteValue($criteria['search']);
            $select->where([new Expression("search ~* {$quoted}")]);
            unset($criteria['search']);
        }

        if ($criteria !== [] && !is_null($criteria)) {
            $select->where($criteria);
        }

        if (isset($options['skip']) && $options['skip'] !== 0) {
            $select->offset($options['skip']);
        }

        if (isset($options['limit'])) {
            $select->limit($options['limit']);
        }

        if (isset($options['sort'])) {
            foreach ($options['sort'] as $field => $direction) {
                $direction = ($direction === 1) ? 'ASC' : 'DESC';
                $select->order("$field $direction");
            }
        }

        if (isset($options['columns'])) {
            $select->columns($options['columns']);
        }

        return $sql->prepareStatementForSqlObject($select)->execute();
    }
}