<?php

declare(strict_types=1);

namespace Application\Model\Service\SharedSpace;

use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Repository\User\LogRepositoryInterface;
use Application\Model\Entity\MemberInvite;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
use Application\Model\Service\Authentication\Service;
use DateTime;
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
        private readonly Service $authenticationService,
        private readonly LoggerInterface $logger,
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
            $this->logger->error('User already belongs to a shared space', [
                'userId' => $userId,
            ]);

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
                'user_id'         => $userId,
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

        $this->logger->info('Shared space created', [
            'event'          => 'shared_space.created',
            'shared_space_id' => $spaceId,
            'user_id'        => $userId,
            'lpas_moved'     => $lpasMoved,
        ]);

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

        return array_map(function ($member) {
            return [
                'sharedSpaceName' => $member->getSharedSpaceName(),
                'userId' => $member->getUserId(),
                'name' => $member->getName(),
                'email' => $member->getEmail(),
                'lastLoginAt' => $member->getLastLoginAt()?->format('Y-m-d\TH:i:s.uO'),
                'isActive' => $member->isActive(),
                'isAdmin' => $member->isAdmin(),
            ];
        }, $members);
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

        $this->logger->info('Member added to shared space', [
            'event'            => 'shared_space.member_added',
            'shared_space_id'  => $sharedSpaceId,
            'added_user_id'    => $userIdToAdd,
            'added_by_user_id' => $userIdAddingMember,
            'is_admin'         => $isAdmin,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function updateMember(string $sharedSpaceId, string $userId, bool $isAdmin, bool $isActive): void
    {
        try {
            $this->sharedSpaceRepository->updateMember($sharedSpaceId, $userId, $isAdmin, $isActive);
        } catch (Throwable $e) {
            $this->logger->error('Unable to update shared space member: ' . $e->getMessage(), [
                'user_id' => $userId,
            ]);

            throw $e;
        }

        $this->logger->info('Shared space member updated', [
            'event'           => 'shared_space.member_updated',
            'shared_space_id' => $sharedSpaceId,
            'user_id'         => $userId,
            'is_admin'        => $isAdmin,
            'is_active'       => $isActive,
        ]);
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

        $this->logger->info('Shared space member deleted', [
            'event' => 'shared_space.member_deleted',
            'shared_space_id' => $sharedSpaceId,
            'deleted_by_user_id' => $userId,
            'deleted_user_id' => $userToDeleteId,
        ]);
    }

    public function getInvites(string $sharedSpaceId): array
    {
        $invites = $this->sharedSpaceRepository->getInvites($sharedSpaceId);

        return array_map(function (MemberInvite $invite) {
            return [
                'id' => $invite->id,
                'fullName' => $invite->firstNames . ' ' . $invite->lastName,
                'email' => $invite->email,
                'isExpired' => $invite->expires->getTimestamp() < (new DateTime())->getTimestamp(),
            ];
        }, $invites);
    }

    /**
     * @return array{id: int, sharedSpaceName: string, inviteCode: string}
     */
    public function invite(MemberInvite $memberInvite): array
    {
        $existingInvites = $this->sharedSpaceRepository->getInvites($memberInvite->sharedSpaceId);
        foreach ($existingInvites as $invite) {
            if ($invite->getEmail() === $memberInvite->getEmail()) {
                throw new InviteAlreadyExistsException();
            }
        }

        $members = $this->sharedSpaceRepository->getMembers($memberInvite->sharedSpaceId);
        foreach ($members as $member) {
            if ($member->getEmail() === $memberInvite->getEmail()) {
                throw new UserAlreadyInSharedSpaceException();
            }
        }

        $sharedSpaceName = $this->sharedSpaceRepository->getSharedSpace($memberInvite->sharedSpaceId);
        $id = $this->sharedSpaceRepository->createInvite($memberInvite);

        return [
            'id' => $id,
            'sharedSpaceName' => $sharedSpaceName,
            'inviteCode' => $memberInvite->code,
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

        $this->logger->info('Shared space invite revoked', [
            'event'           => 'shared_space.invite_revoked',
            'shared_space_id' => $sharedSpaceId,
            'invite_id'       => $inviteId,
            'revoked_by'      => $revokedByUserId,
        ]);
    }

    public function join(string $userId, string $sharedSpaceName, string $accessCode): string
    {
        $this->sharedSpaceRepository->beginTransaction();

        try {
            $sharedSpaceId = $this->sharedSpaceRepository->getSharedSpaceIdForUser($userId);
            if ($sharedSpaceId !== null) {
                throw new UserAlreadyInSharedSpaceException($sharedSpaceId);
            }

            $invite = $this->sharedSpaceRepository->getInviteByCodeAndSharedSpaceName($accessCode, $sharedSpaceName);
            if ($invite === null) {
                throw new InviteNotFoundException();
            }

            $lpasMoved = $this->applicationRepository->setSharedSpaceOwner($userId, $invite->sharedSpaceId);

            $this->logger->info('Reassigned LPA ownership', [
                'user_id' => $userId,
                'shared_space_id' => $invite->sharedSpaceId,
                'count' => $lpasMoved,
            ]);

            $this->sharedSpaceRepository->addMember($invite->sharedSpaceId, $userId, $invite->isAdmin);
            $this->sharedSpaceRepository->deleteInvite($invite->id);

            $this->sharedSpaceRepository->commit();
        } catch (Throwable $e) {
            $this->sharedSpaceRepository->rollback();

            throw $e;
        }

        $this->logger->info('User joined shared space', [
            'event' => 'shared_space.joined',
            'shared_space_id' => $invite->sharedSpaceId,
            'user_id' => $userId,
            'lpas_moved' => $lpasMoved,
        ]);

        return $invite->sharedSpaceId;
    }

    public function import(string $sharedSpaceId, string $userId, #[\SensitiveParameter] string $email, #[\SensitiveParameter] string $password): ?string
    {
        $userToImport = $this->authenticationService->withPassword($email, $password, false);
        if (is_string($userToImport)) {
            return $userToImport;
        }

        if ($userToImport['sharedSpaceId'] !== null) {
            throw new UserAlreadyInSharedSpaceException();
        }

        $this->sharedSpaceRepository->beginTransaction();
        try {
            $lpasMoved = $this->applicationRepository->setSharedSpaceOwner($userToImport['userId'], $sharedSpaceId);

            $this->logger->info('Reassigned LPA ownership', [
                'user_id' => $userToImport['userId'],
                'shared_space_id' => $sharedSpaceId,
                'count' => $lpasMoved,
            ]);

            if (!$this->userRepository->delete($userToImport['userId'])) {
                throw new \RuntimeException('User not deleted');
            }

            $this->sharedSpaceRepository->commit();
        } catch (Throwable $e) {
            $this->sharedSpaceRepository->rollback();

            throw $e;
        }

        $this->logger->info('User imported to shared space', [
            'event' => 'shared_space.import',
            'shared_space_id' => $sharedSpaceId,
            'user_id' => $userId,
            'imported_user_id' => $userToImport['userId'],
            'lpas_moved' => $lpasMoved,
        ]);

        return null;
    }
}
