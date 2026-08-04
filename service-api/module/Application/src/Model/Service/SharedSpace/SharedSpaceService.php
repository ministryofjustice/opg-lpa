<?php

declare(strict_types=1);

namespace Application\Model\Service\SharedSpace;

use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryInterface;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryInterface;
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
                'userId'        => $userId,
                'sharedSpaceId' => $spaceId,
                'count'         => $lpasMoved,
            ]);

            // The creating user becomes the first member of the shared space, so
            // they retain access to the LPAs they just moved into it.
            $this->sharedSpaceRepository->addMember($spaceId, $userId);

            $this->sharedSpaceRepository->commit();
        } catch (Throwable $e) {
            $this->sharedSpaceRepository->rollback();

            $this->logger->error('Unable to create shared space: ' . $e->getMessage(), [
                'class' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'stackTrace' => $e->getTraceAsString(),
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

    public function getMembers(string $sharedSpaceId): array
    {
        $users = $this->sharedSpaceRepository->getMembers($sharedSpaceId);
        $profiles = $this->userRepository->getProfiles(array_map(function ($user) {
            return $user['userId'];
        }, $users));

        return array_map(function ($profile) use ($users) {
            $user = array_find($users, function ($user) use ($profile) {
                return $user['userId'] === $profile->getId();
            });

            return [
                'id' => $profile->getId(),
                'name' => $profile->getName(),
                'email' => $profile->getEmail(),
                'lastLoginAt' => $profile->getLastLoginAt(),
                'isActive' => $user['isActive'] ?? true, // TODO: since we don't set this yet...
                'isAdmin' => $user['isAdmin'] ?? true, // TODO: or this
            ];
        }, $profiles);
    }
}
