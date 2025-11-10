<?php

namespace Application\Model\DataAccess\Postgres;

use DateMalformedStringException;
use DateTime;
use Application\Model\DataAccess\Postgres\AbstractBase;
use Application\Model\DataAccess\Repository\User as UserRepository;

class LogData extends AbstractBase implements UserRepository\LogRepositoryInterface
{
    public const DELETION_LOG_TABLE = 'deletion_log';

    /**
     * Add a document to the log collection.
     *
     * @param array $details
     */
    public function addLog(array $details): void
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
        $statement->execute();
    }

    /**
     * Retrieve a log document based on the identity hash stored against it
     *
     * @param string $identityHash
     * @return array|null
     * @throws DateMalformedStringException
     */
    public function getLogByIdentityHash(string $identityHash): ?array
    {
        $sql = $this->dbWrapper->createSql();
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
