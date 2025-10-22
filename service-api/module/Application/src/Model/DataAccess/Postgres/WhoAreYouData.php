<?php

namespace Application\Model\DataAccess\Postgres;

use DateTime;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\Expression;
use MakeShared\DataModel\WhoAreYou\WhoAreYou;
use Application\Model\DataAccess\Postgres\AbstractBase;
use Application\Model\DataAccess\Repository\Application\WhoRepositoryInterface;

class WhoAreYouData extends AbstractBase implements WhoRepositoryInterface
{
    public const WHO_TABLE = 'who_are_you';

    /**
     * Insert a new 'Who Are You' response.
     *
     * @param WhoAreYou $answer
     */
    public function insert(WhoAreYou $answer): void
    {
        $sql = $this->dbWrapper->createSql();
        $insert = $sql->insert(self::WHO_TABLE);

        $data = [
            'who' => $answer->getWho(),
            'qualifier' => $answer->getQualifier(),
            'logged' => gmdate(DbWrapper::TIME_FORMAT),
        ];

        $insert->columns(array_keys($data));
        $insert->values($data);

        $statement = $sql->prepareStatementForSqlObject($insert);

        $statement->execute();
    }

    /**
     * Return the WhoAreYou values for a specific date range.
     *
     * If no instances of a given category were seen, 0 is expected to be returned for it.
     *
     * @param $start
     * @param $end
     * @param $options
     * @return array
     */
    public function getStatsForTimeRange(DateTime $start, DateTime $end, array $options): array
    {
        $sql = $this->dbWrapper->createSql();
        $select = $sql->select(self::WHO_TABLE);

        $select->columns(['who','count' => new Expression('count(*)')]);

        $select->where([
            new Operator('logged', Operator::OPERATOR_GREATER_THAN_OR_EQUAL_TO, $start->format('c')),
            new Operator('logged', Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO, $end->format('c')),
        ]);

        $select->group('who');

        $results = $sql->prepareStatementForSqlObject($select)->execute();

        //---------------------------

        /*
         * This method is expected to return a 0 if there are no instances of a given category, thus
         * we cannot simply return the response to the query.
         */

        // Gives us an array with the 'who' options as keys, and the count value all set to 0.
        foreach ($options as &$value) {
            $value = [ 'count' => 0 ];
        }

        // Map each DB result to the category.
        foreach ($results as $result) {
            $who = $result['who'];
            if (isset($options[$who])) {
                $options[$who]['count'] = $result['count'];
            }
        }

        return $options;
    }
}
