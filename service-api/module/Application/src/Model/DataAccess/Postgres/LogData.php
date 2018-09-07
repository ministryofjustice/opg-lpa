<?php
namespace Application\Model\DataAccess\Postgres;

use DateTime;
use Zend\Db\Sql\Sql;
use Application\Model\DataAccess\Repository\User as UserRepository;

class LogData extends AbstractBase implements UserRepository\LogRepositoryInterface
{

    const DELETION_LOG_TABLE = 'deletion_log';

    /**
     * Add a document to the log collection.
     *
     * @param array $details
     * @return bool
     */
    public function addLog(array $details) : bool
    {
        $sql = new Sql($this->getZendDb());
        $insert = $sql->insert(self::DELETION_LOG_TABLE);

        $insert->columns(['identity_hash','type','reason','loggedAt']);

        $insert->values([
            'identity_hash' => $details['identity_hash'],
            'type'          => $details['type'],
            'reason'        => $details['reason'],
            'loggedAt'      => $details['loggedAt']->format(self::TIME_FORMAT),
        ]);

        $statement = $sql->prepareStatementForSqlObject($insert);

        try {
            $statement->execute();

        } catch (\Zend\Db\Adapter\Exception\InvalidQueryException $e){
            return false;

        }

        return true;
    }

    /**
     * Retrieve a log document based on the identity hash stored against it
     *
     * @param string $identityHash
     * @return array
     */
    public function getLogByIdentityHash(string $identityHash) : ?array
    {
        $sql    = new Sql($this->getZendDb());
        $select = $sql->select(self::DELETION_LOG_TABLE);

        $select->where(['identity_hash' => $identityHash]);
        $select->order('loggedAt DESC');
        $select->limit(1);

        $result = $sql->prepareStatementForSqlObject($select)->execute();

        if (!$result->isQueryResult() || $result->count() != 1) {
            return null;
        }

        $result = $result->current();

        // Map to the expected DateTime
        $result['loggedAt'] = new DateTime($result['loggedAt']);

        return $result;
    }

}

