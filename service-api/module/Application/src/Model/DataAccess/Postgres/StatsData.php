<?php
namespace Application\Model\DataAccess\Postgres;

use Zend\Db\Sql\Sql;
use Application\Model\DataAccess\Repository\Stats as StatsRepository;

class StatsData extends AbstractBase implements StatsRepository\StatsRepositoryInterface
{

    const STATS_TABLE = 'stats';


    /**
     * Insert a new set of stats into the cache.
     *
     * @param array $stats
     * @return bool
     */
    public function insert(array $stats) : bool
    {
        $sql = new Sql($this->getZendDb());
        $insert = $sql->insert(self::STATS_TABLE);

        $data = [
            'data' => json_encode($stats),
        ];

        $insert->columns(array_keys($data));
        $insert->values($data);

        $statement = $sql->prepareStatementForSqlObject($insert);

        $result = $statement->execute();

        return $result->getAffectedRows() === 1;
    }


    /**
     * Returns the current set of cached stats.
     *
     * @return array|null
     */
    public function getStats() : ?array
    {
        $sql    = new Sql($this->getZendDb());
        $select = $sql->select(self::STATS_TABLE);
        $select->order('id DESC');  // Sense check; should be unnecessary
        $select->limit(1);

        $result = $sql->prepareStatementForSqlObject($select)->execute();

        if (!$result->isQueryResult() || $result->count() !== 1) {
            return null;
        }

        return json_decode($result->current()['data'], true);
    }


    /**
     * Delete all previously cached stats.
     *
     * i.e. truncate table
     *
     */
    public function delete() : bool
    {
        $adapter = $this->getZendDb();

        $adapter->query('TRUNCATE TABLE '.self::STATS_TABLE, $adapter::QUERY_MODE_EXECUTE);

        return true;
    }

}
