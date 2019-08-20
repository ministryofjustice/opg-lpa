<?php
namespace Application\Model\DataAccess\Postgres;

use DateTime;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Operator;
use Zend\Db\Sql\Predicate\Expression;
use Opg\Lpa\DataModel\WhoAreYou\WhoAreYou;
use Application\Model\DataAccess\Repository\Application\WhoRepositoryInterface;

class WhoAreYouData extends AbstractBase implements WhoRepositoryInterface
{

    const WHO_TABLE = 'who_are_you';


    /**
     * Insert a new 'Who Are You' response.
     *
     * @param WhoAreYou $answer
     * @return bool
     */
    public function insert(WhoAreYou $answer) : bool
    {
        $sql = new Sql($this->getZendDb());
        $insert = $sql->insert(self::WHO_TABLE);

        $data = [
            'who'       => $answer->getWho(),
            'qualifier' => $answer->getQualifier(),
            'logged'    => gmdate(self::TIME_FORMAT),
        ];

        $insert->columns(array_keys($data));
        $insert->values($data);

        $statement = $sql->prepareStatementForSqlObject($insert);

        $result = $statement->execute();

        return $result->getAffectedRows() === 1;
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
    public function getStatsForTimeRange(DateTime $start, DateTime $end, array $options) : array
    {
        $sql    = new Sql($this->getZendDb());
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
        foreach($options as $key => &$value) {
            $value = [ 'count' => 0 ];
        }

        // Map each DB result to the category.
        foreach($results as $result) {
            $who = $result['who'];
            if (isset($options[$who])) {
                $options[$who]['count'] = $result['count'];
            }
        }

        return $options;
    }

}
