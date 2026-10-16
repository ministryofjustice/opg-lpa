<?php

declare(strict_types=1);

namespace App\Service\SharedSpace;

use App\Service\ApiClient\Client;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use DateTime;
use Exception;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use MakeShared\DataModel\User\User;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class SharedSpaceService
{
    public const string EMAIL_INVITE_MEMBER = 'email-invite-member';
    public const string EMAIL_SUSPEND_MEMBER = 'email-suspend-member';
    public const string EMAIL_DELETE_MEMBER = 'email-delete-member';

    public function __construct(
        private readonly Client $client,
        private readonly MailTransportInterface $mailTransport,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Creates a shared space for the current user, returning its id.
     *
     * @return string|null The new shared space's id, or null on failure.
     */
    public function create(string $sharedSpaceName): ?string
    {
        try {
            /** @var array<string, mixed>|null $result */
            $result = $this->client->httpPost(
                '/v2/shared-space/create',
                ['name' => $sharedSpaceName],
            );
        } catch (Throwable $e) {
            $this->logger->warning('Shared space creation failed', [
                'exception' => $e,
            ]);

            return null;
        }

        return $result['sharedSpaceId'] ?? null;
    }

    public function get(): mixed
    {
        try {
            $result = $this->client->httpGet('/v2/shared-space');
        } catch (Throwable $e) {
            $this->logger->error('Retrieve shared space failed', [
                'exception' => $e,
            ]);

            return null;
        }

        return $result;
    }

    public function getMember(string $memberUserId): ?SharedSpaceMember
    {
        try {
            $result = $this->client->httpGet('/v2/shared-space/members/' . $memberUserId);
        } catch (Throwable $e) {
            $this->logger->error('Retrieve shared space member failed', [
                'member_user_id' => $memberUserId,
                'exception' => $e,
            ]);

            return null;
        }

        if (!is_array($result) || !isset($result['member']) || !is_array($result['member'])) {
            return null;
        }

        return new SharedSpaceMember($result['member']);
    }

    public function getMemberCount(): ?int
    {
        try {
            $result = $this->client->httpGet('/v2/shared-space/count-members');
        } catch (Throwable $e) {
            $this->logger->error('Retrieve shared space member count failed', [
                'exception' => $e,
            ]);

            return null;
        }

        if (!is_array($result) || !isset($result['count']) || !is_int($result['count'])) {
            return null;
        }

        return $result['count'];
    }

    public function getMembersAndInvites(): ?array
    {
        try {
            $result = $this->client->httpGet('/v2/shared-space/members-and-invites');
        } catch (Throwable $e) {
            $this->logger->error('Retrieve members and invites of shared space failed', [
                'exception' => $e,
            ]);

            return null;
        }

        if (!is_array($result)) {
            return null;
        }

        return [
            'members' => array_map(
                fn (array $member) => new SharedSpaceMember($member),
                $result['members'] ?? [],
            ),
            'invites' => $result['invites'] ?? [],
            'name' => $result['name'] ?? '',
        ];
    }

    public function addMember(string $sharedSpaceId, string $userIdToAdd): bool
    {
        try {
            $this->client->httpPost(
                '/v2/shared-space/members',
                ['sharedSpaceId' => $sharedSpaceId, 'userIdToAdd' => $userIdToAdd]
            );
        } catch (Throwable $e) {
            $this->logger->warning('Adding member to shared space failed', [
                'exception' => $e,
                'sharedSpaceId' => $sharedSpaceId,
                'userIdToAdd' => $userIdToAdd,
            ]);

            return false;
        }

        return true;
    }

    public function updateMember(SharedSpaceMember $member, bool $isAdmin, bool $isActive): bool
    {
        try {
            $this->client->httpPatch(
                '/v2/shared-space/members/' . $member->getUserId(),
                ['isAdmin' => $isAdmin, 'isActive' => $isActive],
            );
        } catch (Throwable $e) {
            $this->logger->error('Updating shared space member failed', [
                'exception' => $e,
                'member_user_id' => $member->getUserId(),
                'is_admin' => $isAdmin,
                'is_active' => $isActive,
            ]);

            return false;
        }

        if (!$isActive) {
            $fullName = $member->getName()->getFirst() . ' ' . $member->getName()->getLast();
            $params = new MailParameters(
                $member->getEmail(),
                self::EMAIL_SUSPEND_MEMBER,
                [
                    'sharedSpaceName' => $member->getSharedSpaceName()
                ],
            );

            try {
                $this->mailTransport->send($params);
            } catch (Exception $e) {
                $this->logger->error('Failed to send suspension email', [
                    'member_user_id' => $member->getUserId(),
                ]);

                return false;
            }
        }

        return true;
    }

    public function deleteMember(SharedSpaceMember $member): bool
    {
        try {
            $this->client->httpDelete('/v2/shared-space/members/' . $member->getUserId());
        } catch (Throwable $e) {
            $this->logger->error('Deleting shared space member failed', [
                'exception' => $e,
                'memberUserId' => $member->getUserId(),
            ]);

            return false;
        }

        try {
            $params = new MailParameters(
                $member->getEmail(),
                self::EMAIL_DELETE_MEMBER,
                ['sharedSpaceName' => $member->getSharedSpaceName()],
            );

            $this->mailTransport->send($params);
        } catch (Throwable $e) {
            $this->logger->error('Failed to send member deleted email', [
                'member_user_id' => $member->getUserId(),
            ]);
        }

        return true;
    }

    /**
     * @throws Throwable|ApiException|ClientExceptionInterface
     */
    public function invite(User $inviter, string $firstNames, string $lastName, string $email, bool $isAdmin): bool
    {
        try {
            $result = $this->client->httpPost(
                '/v2/shared-space/invite',
                [
                    'firstNames' => $firstNames,
                    'lastName' => $lastName,
                    'email' => $email,
                    'isAdmin' => $isAdmin,
                ],
            );
        } catch (Throwable $e) {
            $this->logger->error('Creating shared space invite failed:', ['exception' => $e]);
            throw $e;
        }

        $params = new MailParameters(
            $email,
            self::EMAIL_INVITE_MEMBER,
            [
                'inviteeFirstName' => $firstNames,
                'inviteeEmail' => $email,
                'inviterFullName' => $inviter->getName()->getFirst() . ' ' . $inviter->getName()->getLast(),
                'inviterEmail' => $inviter->getEmail()->getAddress(),
                'sharedSpaceName' => $result['sharedSpaceName'],
                'joiningCode' => $result['inviteCode'],
                'expiryDate' => (new DateTime($result['expires']))->format('j F Y'),
            ],
        );

        try {
            $this->mailTransport->send($params);
        } catch (Exception $e) {
            $this->logger->error('Failed to send invite email', [
                'inviteId' => $result['id'],
                'exception' => $e
            ]);

            return false;
        }

        return true;
    }

    public function revokeInvite(string $inviteId): bool
    {
        try {
            $this->client->httpPost('/v2/shared-space/revoke-invite/' . $inviteId);
        } catch (\Throwable $e) {
            $this->logger->warning('Revoking invite failed', [
                'exception' => $e,
                'inviteId' => $inviteId,
            ]);

            return false;
        }

        return true;
    }

    public function join(string $sharedSpaceName, string $accessCode, string $userEmail): string
    {
        try {
            /** @var array{sharedSpaceId: string} $response */
            $response = $this->client->httpPost(
                '/v2/shared-space/join',
                ['sharedSpaceName' => $sharedSpaceName, 'accessCode' => $accessCode, 'email' => $userEmail],
            );
        } catch (Throwable $e) {
            $this->logger->warning('Join shared space failed', [
                'exception' => $e,
            ]);

            throw $e;
        }

        return $response['sharedSpaceId'];
    }
}
