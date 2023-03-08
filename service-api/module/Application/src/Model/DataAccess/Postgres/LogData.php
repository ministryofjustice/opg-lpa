<?php

namespace Application\Model\DataAccess\Postgres;

use DateTime;
use Laminas\Db\Sql\Sql;
use Application\Model\DataAccess\Postgres\AbstractBase;
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
    public function addLog(array $details): bool
    {
        $sql = $this->dbWrapper->createSql();
        $insert = $sql->insert(self::DELETION_LOG_TABLE);

        $data = [
            'identity_hash' => $details['identity_hash'],
            'type' => $details['type'],
            'reason' => $details['reason'],
            'loggedAt' => $details['loggedAt']->format(DbWrapper::TIME_FORMAT),
        ];

        $insert->columns(array_keys($data));

        $insert->values($data);

        $statement = $sql->prepareStatementForSqlObject($insert);
        $this->getLogger()->debug('%%%%%%%%%%% addLog IDENTITY HASH:' . $details['identity_hash']);

        try {
            $statement->execute();
        } catch (\Laminas\Db\Adapter\Exception\InvalidQueryException $e) {
            $this->getLogger()->debug('%%%%%%%%%% failed to addLog, EXCEPTION:');
            $this->getLogger()->debug('%%%%%%%%%% EXCEPTION: ');
            $this->getLogger()->debug($e->toString);
            $this->getLogger()->debug($e);
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
    public function getLogByIdentityHash(string $identityHash): ?array
    {
        $sql = $this->dbWrapper->createSql();
        $select = $sql->select(self::DELETION_LOG_TABLE);

        $select->where(['identity_hash' => $identityHash]);
        $select->order('loggedAt DESC');
        $select->limit(1);

        $this->getLogger()->debug('%%%%%%%%%% getLogByIdentityHash IDENTITY HASH: ' . print_r($identityHash, true));
        $result = $sql->prepareStatementForSqlObject($select)->execute();

        if (!$result->isQueryResult() || $result->count() != 1) {
            $this->getLogger()->debug('%%%%%%%%%% getLogByIdentityHash bad query result OR result count not 1');
            $this->getLogger()->debug('%%%%%%%%%%' . print_r($identityHash, true));
            return null;
        }

        $result = $result->current();

        // Map to the expected DateTime
        $result['loggedAt'] = new DateTime($result['loggedAt']);

        return $result;
    }
}
