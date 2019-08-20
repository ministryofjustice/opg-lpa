<?php
namespace Application\Model\DataAccess\Postgres;

use DateTime;
use Traversable;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Predicate\Operator;
use Application\Model\DataAccess\Repository\Feedback as FeedbackRepository;

class FeedbackData extends AbstractBase implements FeedbackRepository\FeedbackRepositoryInterface
{

    const FEEDBACK_TABLE = 'feedback';

    /**
     * Insert a new feedback item
     *
     * @param array $feedback
     * @return bool
     */
    public function insert(array $feedback) : bool
    {
        $sql = new Sql($this->getZendDb());
        $insert = $sql->insert(self::FEEDBACK_TABLE);

        $data = [
            'received'  => gmdate(self::TIME_FORMAT),
            'message'   => json_encode($feedback),
        ];

        $insert->columns(array_keys($data));
        $insert->values($data);

        $statement = $sql->prepareStatementForSqlObject($insert);

        try {
            $statement->execute();
        } catch (\Zend\Db\Adapter\Exception\InvalidQueryException $e){
            return false;

        }

        return true;
    }

    /**
     * Return all feedback items for a given date range.
     *
     * @param DateTime $from
     * @param DateTime $to
     * @return mixed
     */
    public function getForDateRange(DateTime $from, DateTime $to) : Traversable
    {
        $sql    = new Sql($this->getZendDb());
        $select = $sql->select(self::FEEDBACK_TABLE);
        $select->order('received ASC');

        $select->where([
            new Operator('received', Operator::OPERATOR_GREATER_THAN_OR_EQUAL_TO, $from->format('c')),
            new Operator('received', Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO, $to->format('c')),
        ]);

        $results = $sql->prepareStatementForSqlObject($select)->execute();

        foreach ($results as $result) {

            if (!empty($result['message'])) {
                $json = json_decode($result['message'], true);
                $result = array_merge($result, $json);
                unset($result['message']);
            }

            $result['received'] = (new DateTime($result['received']))->format('c');

            yield $result;
        }
    }

    /**
     * Delete all feedback received before the passed date.
     *
     * @param DateTime $before
     * @return bool
     */
    public function prune(DateTime $before) : bool
    {
        $sql    = new Sql($this->getZendDb());
        $delete = $sql->delete(self::FEEDBACK_TABLE);

        $delete->where([
            new Operator('received', Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO, $before->format('c')),
        ]);

        $sql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

}
