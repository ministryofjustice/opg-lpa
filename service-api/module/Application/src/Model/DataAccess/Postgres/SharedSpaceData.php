<?php

declare(strict_types=1);

namespace Application\Model\DataAccess\Postgres;

use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Laminas\Db\Adapter\Exception\InvalidQueryException;

class SharedSpaceData extends AbstractBase implements SharedSpaceRepositoryInterface
{
    public const SHARED_SPACE = 'shared_space';
    public const SHARED_SPACE_MEMBERS = 'shared_space_members';

    /**
     * @inheritDoc
     */
    public function create(string $id, array $details): bool
    {
        $sql = $this->dbWrapper->createSql();
        $insert = $sql->insert(self::SHARED_SPACE);

        $data = [
            'id' => $id,
            'name' => $details['name'],
            'created' => $details['created']->format(DbWrapper::TIME_FORMAT),
            'updated' => $details['last_updated']->format(DbWrapper::TIME_FORMAT),
        ];

        $insert->values($data);

        $statement = $sql->prepareStatementForSqlObject($insert);

        try {
            $statement->execute();
        } catch (InvalidQueryException $e) {
            throw($e);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function addMember(string $sharedSpaceId, string $userId): bool
    {
        $sql = $this->dbWrapper->createSql();
        $insert = $sql->insert(self::SHARED_SPACE_MEMBERS);

        $insert->values([
            'sharedSpaceId' => $sharedSpaceId,
            'userId'        => $userId,
            'created'       => gmdate(DbWrapper::TIME_FORMAT),
        ]);

        $statement = $sql->prepareStatementForSqlObject($insert);

        try {
            $statement->execute();
        } catch (InvalidQueryException $e) {
            throw($e);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getSharedSpaceIdForUser(string $userId): ?string
    {
        $result = $this->dbWrapper->select(self::SHARED_SPACE_MEMBERS, ['userId' => $userId], [
            'columns' => ['sharedSpaceId'],
            'limit'   => 1,
        ]);

        if (!$result->isQueryResult() || $result->count() !== 1) {
            return null;
        }

        return $result->current()['sharedSpaceId'];
    }
}
