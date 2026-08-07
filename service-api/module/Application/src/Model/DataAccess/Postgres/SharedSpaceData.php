<?php

declare(strict_types=1);

namespace Application\Model\DataAccess\Postgres;

use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\Service\SharedSpace\MemberNotInSharedSpaceException;
use Application\Model\Entity\MemberInvite;
use DateTime;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;

class SharedSpaceData extends AbstractBase implements SharedSpaceRepositoryInterface
{
    public const string SHARED_SPACE = 'shared_space';
    public const string SHARED_SPACE_MEMBERS = 'shared_space_members';
    public const string SHARED_SPACE_INVITES = 'shared_space_invites';

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
    public function getSharedSpace(string $id): ?string
    {
        $result = $this->dbWrapper->select(self::SHARED_SPACE, ['id' => $id], ['limit' => 1]);

        if (!$result->isQueryResult() || $result->count() !== 1) {
            return null;
        }

        $current = $result->current();

        return $current['name'];
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
    public function getMember(string $sharedSpaceId, string $memberUserId): ?SharedSpaceMember
    {
        $result = $this->dbWrapper->select(self::SHARED_SPACE_MEMBERS, [
            'sharedSpaceId' => $sharedSpaceId,
            'userId'        => $memberUserId,
        ], [
            'columns' => ['userId', 'isAdmin', 'isActive', 'created'],
            'limit'   => 1,
        ]);

        if (!$result->isQueryResult() || $result->count() !== 1) {
            return null;
        }

        return new SharedSpaceMember([
            'sharedSpaceId' => $sharedSpaceId,
            'userId'        => $result->current()['userId'],
            'isAdmin'       => (bool) $result->current()['isAdmin'],
            'isActive'      => (bool) $result->current()['isActive'],
            'createdAt'     => $result->current()['created'],
        ]);
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
    public function isAdmin(string $sharedSpaceId, string $userId): bool
    {
        $result = $this->dbWrapper->select(self::SHARED_SPACE_MEMBERS, [
            'sharedSpaceId' => $sharedSpaceId,
            'userId'        => $userId,
        ], [
            'columns' => ['isAdmin'],
            'limit'   => 1,
        ]);

        if (!$result->isQueryResult() || $result->count() !== 1) {
            return false;
        }

        return (bool) $result->current()['isAdmin'];
    }

    /**
     * @inheritDoc
     */
    public function updateMemberIsAdmin(string $sharedSpaceId, string $userId, bool $isAdmin): void
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

        if ($result->getAffectedRows() !== 1) {
            throw new MemberNotInSharedSpaceException();
        }
    }

    /**
     * @inheritDoc
     */
    public function getInvites(string $sharedSpaceId): array
    {
        $result = $this->dbWrapper->select(self::SHARED_SPACE_INVITES, ['sharedSpaceId' => $sharedSpaceId]);

        if (!$result->isQueryResult()) {
            return [];
        }

        $invites = [];
        foreach ($result as $value) {
            $invites[] = new MemberInvite(
                userId: $value['invitedBy'],
                sharedSpaceId: $value['sharedSpaceId'],
                firstNames: $value['firstNames'],
                lastName: $value['lastName'],
                email: $value['email'],
                isAdmin: $value['isAdmin'],
                code: $value['code'],
                created: new DateTime($value['created']),
                expires: new DateTime($value['expires']),
            );
        }

        return $invites;
    }

    /**
     * @inheritDoc
     */
    public function createInvite(MemberInvite $memberInvite): int
    {
        $sql = $this->dbWrapper->createSql();
        $insert = $sql->insert(self::SHARED_SPACE_INVITES);

        $insert->values([
            'sharedSpaceId' => $memberInvite->sharedSpaceId,
            'invitedBy'     => $memberInvite->userId,
            'firstNames'    => $memberInvite->firstNames,
            'lastName'      => $memberInvite->lastName,
            'email'         => $memberInvite->email,
            'isAdmin'       => $memberInvite->isAdmin,
            'code'          => $memberInvite->code,
            'created'       => $memberInvite->created->format(DbWrapper::TIME_FORMAT),
            'expires'       => $memberInvite->expires->format(DbWrapper::TIME_FORMAT),
        ]);

        $statement = $sql->prepareStatementForSqlObject($insert);

        try {
            $statement->setSql($statement->getSql() . ' RETURNING id');
            $result = $statement->execute();
        } catch (InvalidQueryException $e) {
            throw $e;
        }

        return (int) $result->current();
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
