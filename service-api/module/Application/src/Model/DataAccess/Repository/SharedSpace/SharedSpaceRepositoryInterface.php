<?php

declare(strict_types=1);

namespace Application\Model\DataAccess\Repository\SharedSpace;

use Exception;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;

interface SharedSpaceRepositoryInterface
{
    /**
     * Begin a database transaction covering writes made via this repository
     * and any other repository backed by the same underlying connection
     * (e.g. ApplicationRepositoryInterface).
     *
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * Commit the current database transaction.
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Roll back the current database transaction.
     *
     * @return void
     */
    public function rollback(): void;

    /**
     * Creates a shared space
     *
     * @param string $id
     * @param array $details
     * @return bool
     * @throws Exception
     */
    public function create(string $id, array $details): bool;

    /**
     * Adds a user as a member of a shared space.
     * A user can only be a member of one shared space at a time.
     *
     * @param string $sharedSpaceId
     * @param string $userId
     * @param bool $isAdmin Whether the new member should have admin permissions in the shared space.
     * @return bool
     * @throws Exception
     * @psalm-suppress PossiblyUnusedReturnValue The caller (SharedSpaceService::create()) does
     *     not check this; a failed insert throws instead of returning false.
     */
    public function addMember(string $sharedSpaceId, string $userId, bool $isAdmin = false): bool;

    /**
     * Get the ID of the shared space that the given user is a member of,
     * if any. A user can only be a member of one shared space at a time.
     *
     * @param string $userId
     * @return string|null
     */
    public function getSharedSpaceIdForUser(string $userId): ?string;


    /**
     * @return SharedSpaceMember|null
     */
    public function getMember(string $sharedSpaceId, string $memberUserId): ?SharedSpaceMember;

    /**
     * @return array<int, SharedSpaceMember>
     */
    public function getMembers(string $sharedSpaceId): array;

    /**
     * Whether the given user is an admin member of the given shared space.
     * Returns false if the user is not a member of the shared space at all.
     *
     * @param string $sharedSpaceId
     * @param string $userId
     * @return bool
     */
    public function isAdmin(string $sharedSpaceId, string $userId): bool;

    /**
     * Updates a member's admin permission within a shared space.
     *
     * @param string $sharedSpaceId
     * @param string $userId
     * @param bool $isAdmin
     * @throws Exception
     */
    public function updateMemberIsAdmin(string $sharedSpaceId, string $userId, bool $isAdmin): void;
}
