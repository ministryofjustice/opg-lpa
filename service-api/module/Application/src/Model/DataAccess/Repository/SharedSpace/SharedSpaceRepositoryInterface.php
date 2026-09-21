<?php

declare(strict_types=1);

namespace Application\Model\DataAccess\Repository\SharedSpace;

use Exception;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use Application\Model\Entity\MemberInvite;
use Application\Model\Service\SharedSpace\SharedSpaceNotFoundException;

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

    public function getSharedSpace(string $id): ?string;

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
     * Check if a member with the given email exists in the given shared space.
     */
    public function hasMemberWithEmail(string $sharedSpaceId, string $email): bool;

    /**
     * Whether the given user is an admin member of the given shared space.
     * Returns false if the user is not a member of the shared space at all.
     */
    public function isAdmin(string $sharedSpaceId, string $userId): bool;

    public function updateMember(string $sharedSpaceId, string $userId, bool $isAdmin, bool $isActive): void;

    public function deleteMember(string $sharedSpaceId, string $userId): void;

    public function getInviteByCodeAndSharedSpaceName(string $accessCode, string $sharedSpaceName): ?MemberInvite;

    /**
     * @return array<MemberInvite>
     */
    public function getInvites(string $sharedSpaceId): array;

    /**
     * Create an invite to a new shared space member.
     * @throws InvalidQueryException
     */
    public function createInvite(MemberInvite $memberInvite): int;

    /**
     * Revoke an unused invite to a shared space.
     * @throws InvalidQueryException
     */
    public function deleteInvite(int $inviteId): void;

    /**
     * Check if an invite exists for a given shared space and email.
     */
    public function hasInvite(string $sharedSpaceId, string $email): bool;

    /**
     * Delete a shared space. Callers MUST first delete (or re-home) any
     * shared_space_members and LPA rows that reference this shared space -
     * unlike LPAs, shared_space_members' foreign key to shared_space does
     * NOT cascade on delete (see migration
     * 20260908180500_shared_space_members_remove_cascade_delete.php), so
     * this will throw a foreign key violation if any membership rows still
     * reference it. The applications table's foreign key to shared_space
     * DOES still cascade on delete, so callers MUST delete (or re-home) any
     * LPAs owned by the shared space before calling this - see
     * ApplicationRepositoryInterface::deleteAllForSharedSpace() - otherwise
     * those LPA rows would be hard-deleted rather than left as anonymised
     * placeholders.
     *
     * @throws Exception
     * @throws SharedSpaceNotFoundException If no shared space with the given ID exists.
     */
    public function deleteSharedSpace(string $sharedSpaceId): void;

    /**
     * Returns the number of members in a shared space.
     */
    public function countMembers(string $sharedSpaceId): int;
}
