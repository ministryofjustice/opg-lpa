<?php

declare(strict_types=1);

namespace Application\Model\Service\SharedSpace;

use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\Entity\MemberInvite;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use DateTime;
use DateTimeInterface;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use MakeShared\Logging\MetricsLogger;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class SharedSpaceService
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(
        private readonly SharedSpaceRepositoryInterface $sharedSpaceRepository,
        private readonly ApplicationRepositoryInterface $applicationRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly LogRepositoryInterface $logRepository,
        private readonly LoggerInterface $logger,
        private readonly MetricsLogger $metrics,
    ) {
    }

    /**
     * Creates a shared space and moves ownership of all of the given user's
     * LPAs into it.
     *
     * @param string $name Name of the shared space
     * @param string $userId ID of the user creating the shared space; their LPAs are moved into it
     * @return array{sharedSpaceId: string, name: string, lpasMoved: int}
     * @throws UserAlreadyInSharedSpaceException If the user already belongs to a
     *     shared space (a user may only be a member of one at a time).
     * @throws RuntimeException|Throwable
     */
    public function create(string $name, string $userId): array
    {
        if ($this->sharedSpaceRepository->getSharedSpaceIdForUser($userId) !== null) {
            $this->logger->error('User already belongs to a shared space');

            throw new UserAlreadyInSharedSpaceException();
        }

        // Create a 32 character shared space id.
        $spaceId = bin2hex(random_bytes(16));

        $now = new MillisecondDateTime();

        // All of the writes below must succeed or none of them should be
        // applied - otherwise we could be left with e.g. a shared space
        // that has no members, or LPAs whose ownership was moved but with
        // no member able to access them.
        $this->sharedSpaceRepository->beginTransaction();

        try {
            $created = $this->sharedSpaceRepository->create($spaceId, [
                'name'         => $name,
                'created'      => $now,
                'last_updated' => $now,
            ]);

            if (!$created) {
                throw new RuntimeException('Failed to create shared space');
            }

            // Move ownership of all of the user's LPAs into the new shared space.
            $lpasMoved = $this->applicationRepository->setSharedSpaceOwner($userId, $spaceId);

            $this->logger->info('Reassigned LPA ownership', [
                'shared_space_id' => $spaceId,
                'count'           => $lpasMoved,
            ]);

            // The creating user becomes the first member of the shared space, so
            // they retain access to the LPAs they just moved into it. They're
            // made an admin so they can manage the shared space's membership.
            $this->sharedSpaceRepository->addMember($spaceId, $userId, isAdmin: true);

            $this->sharedSpaceRepository->commit();
        } catch (Throwable $e) {
            $this->sharedSpaceRepository->rollback();

            $this->logger->error('Unable to create shared space: ' . $e->getMessage(), [
                'class' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        $this->metrics->sharedSpaceCreated($spaceId, $lpasMoved);

        return [
            'sharedSpaceId' => $spaceId,
            'name'          => $name,
            'lpasMoved'     => $lpasMoved,
        ];
    }

    public function getName(string $sharedSpaceId): ?string
    {
        return $this->sharedSpaceRepository->getSharedSpace($sharedSpaceId);
    }

    public function getMember(string $sharedSpaceId, string $memberUserId): ?array
    {
        $member = $this->sharedSpaceRepository->getMember($sharedSpaceId, $memberUserId);

        if ($member === null) {
            return null;
        }

        return [
            'sharedSpaceName' => $member->getSharedSpaceName(),
            'userId' => $member->getUserId(),
            'name' => $member->getName(),
            'email' => $member->getEmail(),
            'lastLoginAt' => $member->getLastLoginAt()?->format('Y-m-d\TH:i:s.uO'),
            'isActive' => $member->isActive(),
            'isAdmin' => $member->isAdmin(),
        ];
    }

    public function getMembers(string $sharedSpaceId): array
    {
        $members = $this->sharedSpaceRepository->getMembers($sharedSpaceId);

        return array_map($this->mapMember(...), $members);
    }

    /**
     * @return array{results: array, total: int}
     */
    public function getMembersPaginated(string $sharedSpaceId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $result = $this->sharedSpaceRepository->getMembersPaginated($sharedSpaceId, $offset, $perPage);

        return [
            'results' => array_map($this->mapMember(...), $result['results']),
            'total' => $result['total'],
        ];
    }

    private function mapMember(SharedSpaceMember $member): array
    {
        return [
            'sharedSpaceName' => $member->getSharedSpaceName(),
            'userId' => $member->getUserId(),
            'name' => $member->getName(),
            'email' => $member->getEmail(),
            'lastLoginAt' => $member->getLastLoginAt()?->format('Y-m-d\TH:i:s.uO'),
            'isActive' => $member->isActive(),
            'isAdmin' => $member->isAdmin(),
        ];
    }

    public function isAdmin(string $sharedSpaceId, string $userId): bool
    {
        return $this->sharedSpaceRepository->isAdmin($sharedSpaceId, $userId);
    }

    /**
     * @throws Throwable
     */
    public function addMember(string $sharedSpaceId, string $userIdToAdd, string $userIdAddingMember, bool $isAdmin = false): void
    {
        if ($this->sharedSpaceRepository->getSharedSpaceIdForUser($userIdToAdd) !== null) {
            $this->logger->error('User already belongs to a shared space', [
                'user_id' => $userIdToAdd,
            ]);

            throw new UserAlreadyInSharedSpaceException();
        }

        $this->sharedSpaceRepository->beginTransaction();

        try {
            // Move ownership of all of the user's LPAs into the new shared space.
            $lpasMoved = $this->applicationRepository->setSharedSpaceOwner($userIdToAdd, $sharedSpaceId);

            $this->logger->info('Reassigned LPA ownership', [
                'user_id'         => $userIdToAdd,
                'shared_space_id' => $sharedSpaceId,
                'count'           => $lpasMoved,
            ]);

            $this->sharedSpaceRepository->addMember($sharedSpaceId, $userIdToAdd, $isAdmin);
            $this->sharedSpaceRepository->commit();
        } catch (Throwable $e) {
            $this->sharedSpaceRepository->rollback();

            $this->logger->error('Unable to add member to shared space: ' . $e->getMessage(), [
                'shared_space_id' => $sharedSpaceId,
            ]);

            throw $e;
        }

        $this->metrics->sharedSpaceMemberJoined($isAdmin, $sharedSpaceId, $userIdToAdd, $lpasMoved, $userIdAddingMember);
    }

    /**
     * @throws Throwable
     */
    public function updateMember(string $sharedSpaceId, string $userId, bool $isAdmin, bool $isActive): void
    {
        try {
            $existing = $this->sharedSpaceRepository->getMember($sharedSpaceId, $userId);

            if ($existing === null) {
                throw new MemberNotInSharedSpaceException();
            }

            $this->sharedSpaceRepository->updateMember($sharedSpaceId, $userId, $isAdmin, $isActive);
        } catch (Throwable $e) {
            $this->logger->error('Unable to update shared space member: ' . $e->getMessage(), [
                'user_id' => $userId,
            ]);

            throw $e;
        }

        if ($isAdmin !== $existing->isAdmin()) {
            $isAdmin
                ? $this->metrics->sharedSpaceMemberPromotedToAdmin($sharedSpaceId, $userId)
                : $this->metrics->sharedSpaceMemberDemotedFromAdmin($sharedSpaceId, $userId);
        }

        if ($isActive !== $existing->isActive()) {
            $isActive
                ? $this->metrics->sharedSpaceMemberActivated($sharedSpaceId, $userId)
                : $this->metrics->sharedSpaceMemberSuspended($sharedSpaceId, $userId);
        }
    }

    /**
     * @throws Throwable
     */
    public function deleteMember(string $sharedSpaceId, string $userId, string $userToDeleteId): void
    {
        $this->sharedSpaceRepository->beginTransaction();

        try {
            $user = $this->userRepository->getById($userToDeleteId);

            $this->sharedSpaceRepository->deleteMember($sharedSpaceId, $userToDeleteId);

            if (!$this->userRepository->delete($userToDeleteId)) {
                throw new \RuntimeException('User not deleted');
            }

            $this->logRepository->addLog([
                'identity_hash' => hash('sha512', strtolower(trim($user->username()))),
                'type'          => 'account-deleted',
                'reason'        => 'Deleted from shared space',
                'loggedAt'      => new MillisecondDateTime(),
            ]);

            $this->sharedSpaceRepository->commit();
        } catch (Throwable $e) {
            $this->sharedSpaceRepository->rollback();

            $this->logger->error('Unable to delete shared space member: ' . $e->getMessage(), [
                'user_id' => $userToDeleteId,
            ]);

            throw $e;
        }

        $this->metrics->userDeleted('removed_from_shared_space', $userToDeleteId);

        $this->metrics->sharedSpaceMemberDeletedByAdmin($sharedSpaceId, $userId, $userToDeleteId);
    }

    public function getInvites(string $sharedSpaceId): array
    {
        $invites = $this->sharedSpaceRepository->getInvites($sharedSpaceId);

        return array_map($this->mapInvite(...), $invites);
    }

    /**
     * @return array{results: array, total: int}
     */
    public function getInvitesPaginated(string $sharedSpaceId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $result = $this->sharedSpaceRepository->getInvitesPaginated($sharedSpaceId, $offset, $perPage);

        return [
            'results' => array_map($this->mapInvite(...), $result['results']),
            'total' => $result['total'],
        ];
    }

    private function mapInvite(MemberInvite $invite): array
    {
        return [
            'id' => $invite->id,
            'fullName' => $invite->firstNames . ' ' . $invite->lastName,
            'email' => $invite->email,
            'createdAt' => $invite->created->format('Y-m-d\TH:i:s.uO'),
            'isExpired' => $invite->expires->getTimestamp() < (new DateTime())->getTimestamp(),
        ];
    }

    /**
     * @return array{id: int, sharedSpaceName: string, inviteCode: string, expires: string}
     */
    public function invite(MemberInvite $memberInvite): array
    {
        if ($this->sharedSpaceRepository->hasInvite($memberInvite->sharedSpaceId, $memberInvite->email)) {
            throw new InviteAlreadyExistsException();
        }

        if ($this->sharedSpaceRepository->hasMemberWithEmail($memberInvite->sharedSpaceId, $memberInvite->email)) {
            throw new UserAlreadyInSharedSpaceException();
        }

        $sharedSpaceName = $this->sharedSpaceRepository->getSharedSpace($memberInvite->sharedSpaceId);
        $id = $this->sharedSpaceRepository->createInvite($memberInvite);

        $this->metrics->sharedSpaceMemberInvited(
            $memberInvite->isAdmin,
            $memberInvite->sharedSpaceId,
            $id,
            $memberInvite->userId,
        );

        return [
            'id' => $id,
            'sharedSpaceName' => $sharedSpaceName,
            'inviteCode' => $memberInvite->code,
            'expires' => $memberInvite->expires->format('Y-m-d\TH:i:s.u0'),
        ];
    }

    /**
     * @throws Throwable
     */
    public function revokeInvite(string $sharedSpaceId, int $inviteId, string $revokedByUserId): void
    {
        try {
            $this->sharedSpaceRepository->deleteInvite($inviteId);
        } catch (Throwable $e) {
            $this->logger->error('Unable to revoke shared space invite: ' . $e->getMessage(), [
                'shared_space_id' => $sharedSpaceId,
                'invite_id' => $inviteId,
            ]);

            throw $e;
        }

        $this->metrics->sharedSpaceMemberInviteRevoked($sharedSpaceId, $inviteId, $revokedByUserId);
    }

    /**
     * Gives an invite a new code and expiry date, returning what is needed to email it again.
     *
     * @return array{firstNames: string, fullName: string, email: string, sharedSpaceName: ?string, inviteCode: string, expires: string}
     * @throws InviteNotFoundException|Throwable
     */
    public function resendInvite(
        string $sharedSpaceId,
        int $inviteId,
        string $code,
        DateTimeInterface $expires,
        string $resentByUserId,
    ): array {
        try {
            $this->sharedSpaceRepository->renewInvite($inviteId, $sharedSpaceId, $code, $expires);
        } catch (Throwable $e) {
            $this->logger->error('Unable to resend shared space invite', [
                'shared_space_id' => $sharedSpaceId,
                'invite_id' => $inviteId,
                'exception' => $e,
            ]);

            throw $e;
        }

        $invites = $this->sharedSpaceRepository->getInvites($sharedSpaceId);
        $invite = array_find($invites, fn (MemberInvite $invite) => $invite->id === $inviteId)
            ?? throw new InviteNotFoundException();

        $this->metrics->sharedSpaceMemberInviteResent($sharedSpaceId, $inviteId, $resentByUserId);

        return [
            'firstNames' => $invite->firstNames,
            'fullName' => $invite->firstNames . ' ' . $invite->lastName,
            'email' => $invite->email,
            'sharedSpaceName' => $this->sharedSpaceRepository->getSharedSpace($sharedSpaceId),
            'inviteCode' => $code,
            'expires' => $expires->format('Y-m-d\TH:i:s.u0'),
        ];
    }

    public function join(string $userId, string $sharedSpaceName, string $accessCode, string $userEmail): string
    {
        $this->sharedSpaceRepository->beginTransaction();

        try {
            $sharedSpaceId = $this->sharedSpaceRepository->getSharedSpaceIdForUser($userId);
            if ($sharedSpaceId !== null) {
                throw new UserAlreadyInSharedSpaceException($sharedSpaceId);
            }

            $invite = $this->sharedSpaceRepository->getInvite($accessCode, $sharedSpaceName, $userEmail);
            $lpasMoved = $this->applicationRepository->setSharedSpaceOwner($userId, $invite->sharedSpaceId);

            $this->logger->info('Reassigned LPA ownership', [
                'shared_space_id' => $invite->sharedSpaceId,
                'count' => $lpasMoved,
            ]);

            $this->sharedSpaceRepository->addMember($invite->sharedSpaceId, $userId, $invite->isAdmin);
            $this->sharedSpaceRepository->deleteInvites($userEmail);

            $this->sharedSpaceRepository->commit();
        } catch (Throwable $e) {
            $this->sharedSpaceRepository->rollback();

            $this->metrics->sharedSpaceMemberFailedToJoin($this->joinFailureReason($e), $userId);

            throw $e;
        }

        $this->metrics->sharedSpaceMemberJoined($invite->isAdmin, $invite->sharedSpaceId, $userId, $lpasMoved);

        return $invite->sharedSpaceId;
    }

    private function joinFailureReason(Throwable $e): string
    {
        return match (true) {
            $e instanceof UserAlreadyInSharedSpaceException => 'already_in_shared_space',
            $e instanceof InviteNotFoundException => 'invite_not_found',
            $e instanceof InviteNotMatchedException => 'invite_not_matched',
            $e instanceof InviteExpiredException => 'invite_expired',
            default => 'error',
        };
    }

    public function countMembers(string $sharedSpaceId): int
    {
        return $this->sharedSpaceRepository->countMembers($sharedSpaceId);
    }

    /**
     * @throws Throwable
     */
    public function deleteAccount(string $sharedSpaceId, string $userId): void
    {
        $this->sharedSpaceRepository->beginTransaction();
        $lpasDeleted = 0;

        try {
            $user = $this->userRepository->getById($userId);
            if ($user === null) {
                throw new RuntimeException('User not found');
            }

            $memberCount = $this->sharedSpaceRepository->countMembers($sharedSpaceId);
            if ($memberCount === 0) {
                throw new RuntimeException('No members found in shared space');
            }

            $isLastMember = $memberCount === 1;

            if (!$isLastMember && !$this->sharedSpaceRepository->hasOtherAdmin($sharedSpaceId, $userId)) {
                throw new NoOtherAdminException();
            }

            // The shared_space_members -> shared_space foreign key does not
            // cascade on delete, so the membership row must always be
            // deleted explicitly before the shared space itself.
            $this->sharedSpaceRepository->deleteMember($sharedSpaceId, $userId);

            if ($isLastMember) {
                $lpasDeleted = $this->applicationRepository->deleteAllForSharedSpace($sharedSpaceId);
                $this->sharedSpaceRepository->deleteSharedSpace($sharedSpaceId);
            }

            if (!$this->userRepository->delete($userId)) {
                throw new RuntimeException('User not deleted');
            }

            $this->logRepository->addLog([
                'identity_hash' => hash('sha512', strtolower(trim($user->username()))),
                'type'          => 'account-deleted',
                'reason'        => 'User deleted their account',
                'loggedAt'      => new MillisecondDateTime(),
            ]);

            $this->sharedSpaceRepository->commit();
        } catch (Throwable $e) {
            $this->sharedSpaceRepository->rollback();

            $this->logger->error('Unable to delete shared space member account: ' . $e->getMessage(), [
                'shared_space_id' => $sharedSpaceId,
            ]);

            throw $e;
        }

        $this->metrics->userDeleted('user-initiated', $userId);

        if ($isLastMember) {
            $this->metrics->sharedSpaceDeleted($sharedSpaceId, $userId, $lpasDeleted);
        }

        $this->metrics->sharedSpaceMemberDeletedBySelf($sharedSpaceId, $userId, $isLastMember);
    }

    /**
     * @return array{results: array, total: int}
     */
    public function matchSharedSpaces(string $query, array $options = []): array
    {
        return $this->sharedSpaceRepository->matchSharedSpaces($query, $options);
    }

    public function hasExactlyOneActiveAdmin(string $sharedSpaceId): bool
    {
        return $this->sharedSpaceRepository->hasExactlyOneActiveAdmin($sharedSpaceId);
    }
}
