<?php
namespace Application\Model\DataAccess\Postgres;

use DateTime;
use Traversable;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Predicate\Operator;
use Application\Model\DataAccess\Repository\Feedback as FeedbackRepository;

class FeedbackData implements FeedbackRepository\FeedbackRepositoryInterface
{
    const FEEDBACK_TABLE = 'feedback';

    /**
     * Wrapper around db adapter and SQL generation.
     * @var DbWrapper
     */
    private $dbWrapper;

    /**
     * Constructor.
     * @param ZendDbAdapter $adapter
     */
    public final function __construct(DbWrapper $dbWrapper)
    {
        $this->dbWrapper = $dbWrapper;
    }

    /**
     * Insert a new feedback item
     *
     * @param array $feedback
     * @return bool
     */
    public function insert(array $feedback) : bool
    {
        $sql = $this->dbWrapper->createSql();
        $insert = $sql->insert(self::FEEDBACK_TABLE);

        $data = [
            'received'  => gmdate(DbWrapper::TIME_FORMAT),
            'message'   => json_encode($feedback),
        ];

        $insert->columns(array_keys($data));
        $insert->values($data);

        $sql = $sql->prepareStatementForSqlObject($insert);

        try {
            $sql->execute();
        }
        catch (\Laminas\Db\Adapter\Exception\InvalidQueryException $e) {
            $this->getLogger()->err('Error running insert query for feedback');
            $this->getLogger()->err($e->getMessage());
            $this->getLogger()->err($e->getTraceAsString());
            return false;
        }

        return true;
    }

    /**
     * Return all feedback items for a given date range.
     *
     * @param DateTime $from
     * @param DateTime $to
     * @return Traversable
     */
    public function getForDateRange(DateTime $from, DateTime $to) : Traversable
    {
        $criteria = [
            new Operator('received', Operator::OPERATOR_GREATER_THAN_OR_EQUAL_TO, $from->format('c')),
            new Operator('received', Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO, $to->format('c')),
        ];

        $options = [
            'sort' => ['received' => 1]
        ];

        $results = $this->dbWrapper->select(self::FEEDBACK_TABLE, $criteria, $options);

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
        $sql = $this->dbWrapper->createSql();
        $delete = $sql->delete(self::FEEDBACK_TABLE);

        $delete->where([
            new Operator('received', Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO, $before->format('c')),
        ]);

        $sql->prepareStatementForSqlObject($delete)->execute();

        return true;
    }

}