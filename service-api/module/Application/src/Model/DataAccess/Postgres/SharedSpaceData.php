<?php

declare(strict_types=1);

namespace Application\Model\DataAccess\Postgres;

use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;

class SharedSpaceData extends AbstractBase implements SharedSpaceRepositoryInterface
{
    public const string SHARED_SPACE = 'shared_space';
    public const string SHARED_SPACE_MEMBERS = 'shared_space_members';

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
    public function addMember(string $sharedSpaceId, string $userId, bool $isAdmin = false): bool
    {
        $sql = $this->dbWrapper->createSql();
        $insert = $sql->insert(self::SHARED_SPACE_MEMBERS);

        $insert->values([
            'sharedSpaceId' => $sharedSpaceId,
            'userId'        => $userId,
            'isAdmin'       => $isAdmin,
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

    /**
     * @inheritDoc
     */
    public function getMembers(string $sharedSpaceId): array
    {
        $result = $this->dbWrapper->select(self::SHARED_SPACE_MEMBERS, ['sharedSpaceId' => $sharedSpaceId], [
            'columns' => ['userId', 'isAdmin', 'isActive', 'created'],
        ]);

        if (!$result->isQueryResult()) {
            return [];
        }

        $members = [];

        foreach ($result as $row) {
            $members[] = new SharedSpaceMember([
                'sharedSpaceId' => $sharedSpaceId,
                'userId'        => $row['userId'],
                'isAdmin'       => (bool) $row['isAdmin'],
                'isActive'      => (bool) $row['isActive'],
                'createdAt'     => $row['created'],
            ]);
        }

        return $members;
    }

    /**
     * @inheritDoc
     */
    public function updateMemberIsAdmin(string $sharedSpaceId, string $userId, bool $isAdmin): bool
    {
        $sql = $this->dbWrapper->createSql();
        $update = $sql->update(self::SHARED_SPACE_MEMBERS);
        $update->where([
            'sharedSpaceId' => $sharedSpaceId,
            'userId'        => $userId,
        ]);
        $update->set([
            'isAdmin' => $isAdmin,
        ]);

        $statement = $sql->prepareStatementForSqlObject($update);

        try {
            $result = $statement->execute();
        } catch (InvalidQueryException $e) {
            throw($e);
        }

        return $result->getAffectedRows() === 1;
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): void
    {
        $this->dbWrapper->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commit(): void
    {
        $this->dbWrapper->commit();
    }

    /**
     * @inheritDoc
     */
    public function rollback(): void
    {
        $this->dbWrapper->rollback();
    }
}
