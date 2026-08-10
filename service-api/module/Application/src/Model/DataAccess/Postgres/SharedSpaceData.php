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
        $insert = $sql
            ->insert(self::SHARED_SPACE)
            ->values([
                'id' => $id,
                'name' => $details['name'],
                'created' => $details['created']->format(DbWrapper::TIME_FORMAT),
                'updated' => $details['last_updated']->format(DbWrapper::TIME_FORMAT),
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
    public function addMember(string $sharedSpaceId, string $userId, bool $isAdmin = false): bool
    {
        $sql = $this->dbWrapper->createSql();
        $insert = $sql
            ->insert(self::SHARED_SPACE_MEMBERS)
            ->values([
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
    public function updateMember(string $sharedSpaceId, string $userId, bool $isAdmin, bool $isActive): void
    {
        $sql = $this->dbWrapper->createSql();
        $update = $sql
            ->update(self::SHARED_SPACE_MEMBERS)
            ->where([
                'sharedSpaceId' => $sharedSpaceId,
                'userId'        => $userId,
            ])
            ->set([
                'isAdmin' => $isAdmin,
                'isActive' => $isActive,
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
    public function getInviteByCodeAndSharedSpaceName(string $accessCode, string $sharedSpaceName): ?MemberInvite
    {
        $sql = $this->dbWrapper->createSql();
        $select = $sql->select()
            ->from(['invite' => self::SHARED_SPACE_INVITES])
            ->join(['space' => self::SHARED_SPACE], 'invite.sharedSpaceId = space.id', ['name'])
            ->where([
                'invite.code' => $accessCode,
                'space.name' => $sharedSpaceName,
            ])
            ->limit(1);

        $result = $sql->prepareStatementForSqlObject($select)->execute();

        if (!$result->isQueryResult() || $result->count() !== 1) {
            return null;
        }

        $value = $result->current();

        return new MemberInvite(
            id: (int) $value['id'],
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
                id: $value['id'],
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
        $insert = $sql
            ->insert(self::SHARED_SPACE_INVITES)
            ->values([
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
            $statement->setSql($statement->getSql() . ' RETURNING "id"');
            $result = $statement->execute();
        } catch (InvalidQueryException $e) {
            throw $e;
        }

        return (int) $result->current();
    }

    /**
     * @inheritDoc
     */
    public function deleteInvite(int $inviteId): void
    {
        $sql = $this->dbWrapper->createSql();
        $delete = $sql
            ->delete(self::SHARED_SPACE_INVITES)
            ->where(['id' => $inviteId]);

        try {
            $sql->prepareStatementForSqlObject($delete)->execute();
        } catch (InvalidQueryException $e) {
            throw $e;
        }
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
