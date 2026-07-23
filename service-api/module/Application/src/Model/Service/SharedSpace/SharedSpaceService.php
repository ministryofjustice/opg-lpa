<?php

declare(strict_types=1);

namespace Application\Model\Service\SharedSpace;

use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\DataAccess\Repository\SharedSpace\SharedSpaceRepositoryTrait;
use Application\Model\Service\AbstractService;
use RuntimeException;
use Throwable;

class SharedSpaceService extends AbstractService
{
    use ApplicationRepositoryTrait;
    use SharedSpaceRepositoryTrait;

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
        if ($this->getSharedSpaceRepository()->getSharedSpaceIdForUser($userId) !== null) {
            throw new UserAlreadyInSharedSpaceException();
        }

        // Create a 32 character shared space id.
        $spaceId = bin2hex(random_bytes(16));

        $now = new MillisecondDateTime();

        // All of the writes below must succeed or none of them should be
        // applied - otherwise we could be left with e.g. a shared space
        // that has no members, or LPAs whose ownership was moved but with
        // no member able to access them.
        $this->getSharedSpaceRepository()->beginTransaction();

        try {
            $created = $this->getSharedSpaceRepository()->create($spaceId, [
                'name'         => $name,
                'created'      => $now,
                'last_updated' => $now,
            ]);

            if (!$created) {
                throw new RuntimeException('Failed to create shared space');
            }

            // Move ownership of all of the user's LPAs into the new shared space.
            $lpasMoved = $this->reassignLpaOwner($userId, $spaceId);

            // The creating user becomes the first member of the shared space, so
            // they retain access to the LPAs they just moved into it.
            $this->getSharedSpaceRepository()->addMember($spaceId, $userId);

            $this->getSharedSpaceRepository()->commit();
        } catch (Throwable $e) {
            $this->getSharedSpaceRepository()->rollback();

            throw $e;
        }

        $this->log('info', 'Shared space created', [
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
}
