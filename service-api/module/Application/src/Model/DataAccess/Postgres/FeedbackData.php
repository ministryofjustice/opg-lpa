<?php
namespace Application\Model\DataAccess\Postgres;

use DateTime;
use Zend\Db\Sql\Sql;
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
            'details'   => json_encode($feedback),
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
     * Return all feedback items for the given query.
     *
     * @param array $query
     * @return array
     */
    public function get(array $query) : array
    {
        die(__METHOD__.' not done yet');
    }

    /**
     * Delete all feedback received before teh passed date.
     *
     * @param DateTime $before
     * @return bool
     */
    public function prune(DateTime $before) : bool
    {
        die(__METHOD__.' not done yet');
    }

}
