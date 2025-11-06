<?php

namespace Application\Model\DataAccess\Postgres;

use Application\Model\DataAccess\Postgres\AbstractBase;
use Application\Model\DataAccess\Repository\Stats as StatsRepository;

class StatsData extends AbstractBase implements StatsRepository\StatsRepositoryInterface
{
    public const STATS_TABLE = 'stats';

    /**
     * Insert a new set of stats into the cache.
     *
     * @param array $stats
     */
    public function insert(array $stats): void
    {
        $sql = $this->dbWrapper->createSql();
        $insert = $sql->insert(self::STATS_TABLE);

        $data = [
            'data' => json_encode($stats),
        ];

        $insert->columns(array_keys($data));
        $insert->values($data);

        $statement = $sql->prepareStatementForSqlObject($insert);

        $statement->execute();
    }

    /**
     * Returns the current set of cached stats.
     *
     * @return array|null
     */
    public function getStats(): ?array
    {
        $sql = $this->dbWrapper->createSql();
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
    public function delete(): void
    {
        $this->dbWrapper->rawQuery('TRUNCATE TABLE ' . self::STATS_TABLE);
    }
}
