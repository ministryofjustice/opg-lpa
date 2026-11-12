<?php

declare(strict_types=1);

namespace MakeShared\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class MetricsLogger
{
    public const string SHARED_SPACE_CREATED = 'event.shared_space.created';
    public const string SHARED_SPACE_DELETED = 'event.shared_space.deleted';

    public const string SHARED_SPACE_MEMBER_FAILED_TO_JOIN = 'event.shared_space.member.failed_to_join';
    public const string SHARED_SPACE_MEMBER_DELETED = 'event.shared_space.member.deleted';
    public const string SHARED_SPACE_MEMBER_JOINED = 'event.shared_space.member.joined';
    public const string SHARED_SPACE_MEMBER_INVITED = 'event.shared_space.member.invited';
    public const string SHARED_SPACE_MEMBER_INVITE_REVOKED = 'event.shared_space.member.invite_revoked';
    public const string SHARED_SPACE_MEMBER_PROMOTED_TO_ADMIN = 'event.shared_space.member.promoted_to_admin';
    public const string SHARED_SPACE_MEMBER_DEMOTED_FROM_ADMIN = 'event.shared_space.member.demoted_from_admin';
    public const string SHARED_SPACE_MEMBER_SUSPENDED = 'event.shared_space.member.suspended';
    public const string SHARED_SPACE_MEMBER_ACTIVATED = 'event.shared_space.member.activated';

    public const string USER_CREATED = 'event.user.created';
    public const string USER_DELETED = 'event.user.deleted';
    public const string USER_FAILED_TO_LOGIN = 'event.user.failed_to_login';
    public const string USER_FAILED_TO_LOGOUT = 'event.user.failed_to_logout';
    public const string USER_FAILED_TO_LINK_EXISTING_MAKE_ACCOUNT = 'event.user.failed_to_link_existing_make_account';
    public const string USER_IDENTIFIED_AS_LAY = 'event.user.identified_as_lay';
    public const string USER_IDENTIFIED_AS_PROFESSIONAL = 'event.user.identified_as_professional';
    public const string USER_LINKED_EXISTING_MAKE_ACCOUNT = 'event.user.linked_existing_make_account';
    public const string USER_LOGGED_IN = 'event.user.logged_in';
    public const string USER_LOGGED_OUT = 'event.user.logged_out';
    public const string USER_SESSION_ENDED_BY_ONE_LOGIN = 'event.user.session_ended_by_one_login';
    public const string USER_REFUSED_PASSWORD_RESET = 'event.user.refused_password_reset';
    public const string USER_RESET_LEGACY_ACCOUNT_PASSWORD = 'event.user.reset_legacy_account_password';

    public const string CONTEXT_DIMENSIONS_KEY = 'metric_dimensions';

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function userCreated(string $userId): void
    {
        $this->log('User created', self::USER_CREATED, [], ['user_id' => $userId]);
    }

    /**
     * @param string $reason Why the login failed, e.g. state_mismatch, token_exchange_failed
     * @param array<string, mixed> $context Extra log fields
     * @param string $level A LogLevel constant. Use ERROR for unexpected failures, WARNING for expected ones
     */
    public function userFailedToLogin(
        string $reason,
        array $context = [],
        string $level = LogLevel::WARNING,
    ): void {
        $this->log(
            'User failed to login',
            self::USER_FAILED_TO_LOGIN,
            ['Reason' => $reason],
            $context,
            $level,
        );
    }

    public function userIdentifiedAsLay(string $userId): void
    {
        $this->log('User identified as lay', self::USER_IDENTIFIED_AS_LAY, [], ['user_id' => $userId]);
    }

    public function userIdentifiedAsProfessional(string $userId): void
    {
        $this->log(
            'User identified as professional',
            self::USER_IDENTIFIED_AS_PROFESSIONAL,
            [],
            ['user_id' => $userId],
        );
    }

    public function userLinkedExistingMakeAccount(string $userId): void
    {
        $this->log(
            'User linked existing Make account',
            self::USER_LINKED_EXISTING_MAKE_ACCOUNT,
            [],
            ['user_id' => $userId],
        );
    }

    public function userLoggedIn(string $userId): void
    {
        $this->log('User logged in', self::USER_LOGGED_IN, [], ['user_id' => $userId]);
    }

    public function userLoggedOut(): void
    {
        $this->log('User logged out', self::USER_LOGGED_OUT, [], []);
    }

    public function userSessionEndedByOneLogin(string $userId): void
    {
        $this->log(
            'User session ended by One Login',
            self::USER_SESSION_ENDED_BY_ONE_LOGIN,
            [],
            ['user_id' => $userId],
        );
    }

    public function userRefusedPasswordReset(): void
    {
        $this->log('Password reset refused for a One Login account', self::USER_REFUSED_PASSWORD_RESET, [], []);
    }

    public function userResetLegacyAccountPassword(): void
    {
        $this->log(
            'User successfully changed their password via a reset',
            self::USER_RESET_LEGACY_ACCOUNT_PASSWORD,
            [],
            [],
        );
    }

    /**
     * @param string $reason Why the logout failed, e.g. invalid_token, replayed_jti
     * @param array<string, mixed> $context Extra log fields
     * @param string $level A LogLevel constant. Use ERROR for unexpected failures, WARNING for expected ones
     */
    public function userFailedToLogout(
        string $reason,
        array $context = [],
        string $level = LogLevel::INFO,
    ): void {
        $this->log('User failed to logout', self::USER_FAILED_TO_LOGOUT, ['Reason' => $reason], $context, $level);
    }

    /**
     * @param string $reason Why the link was rejected, e.g. a LinkReason constant
     */
    public function userFailedToLinkExistingMakeAccount(string $reason): void
    {
        $this->log(
            'User failed to link existing Make account',
            self::USER_FAILED_TO_LINK_EXISTING_MAKE_ACCOUNT,
            ['Reason' => $reason],
            [],
        );
    }

    /**
     * @param string $reason Why the account was deleted, e.g. user-initiated, expired, unactivated
     */
    public function userDeleted(string $reason, string $userId): void
    {
        $this->log('User deleted', self::USER_DELETED, ['Reason' => $reason], ['user_id' => $userId]);
    }

    public function sharedSpaceCreated(string $sharedSpaceId, int $lpasMoved): void
    {
        $this->log('Shared space created', self::SHARED_SPACE_CREATED, [], [
            'shared_space_id' => $sharedSpaceId,
            'lpas_moved' => $lpasMoved,
        ]);
    }

    public function sharedSpaceDeleted(string $sharedSpaceId, string $deletedByUserId, int $lpasDeleted): void
    {
        $this->log('Shared space deleted', self::SHARED_SPACE_DELETED, [], [
            'shared_space_id' => $sharedSpaceId,
            'deleted_by_user_id' => $deletedByUserId,
            'lpas_deleted' => $lpasDeleted,
        ]);
    }

    /**
     * @param string $reason Why the join failed, e.g. invalid_invite_code, expired_invite_code, already_member etc
     */
    public function sharedSpaceMemberFailedToJoin(string $reason, string $userId): void
    {
        $this->log('User failed to join shared space', self::SHARED_SPACE_MEMBER_FAILED_TO_JOIN, [
            'Reason' => $reason,
        ], [
            'user_id' => $userId,
        ]);
    }

    public function sharedSpaceMemberDeletedByAdmin(
        string $sharedSpaceId,
        string $deletedByUserId,
        string $deletedUserId,
    ): void {
        $this->log('Shared space member deleted', self::SHARED_SPACE_MEMBER_DELETED, ['DeletedBy' => 'admin'], [
            'shared_space_id' => $sharedSpaceId,
            'deleted_by_user_id' => $deletedByUserId,
            'deleted_user_id' => $deletedUserId,
        ]);
    }

    public function sharedSpaceMemberDeletedBySelf(string $sharedSpaceId, string $userId, bool $wasLastMember): void
    {
        $this->log('Shared space member deleted', self::SHARED_SPACE_MEMBER_DELETED, ['DeletedBy' => 'self'], [
            'shared_space_id' => $sharedSpaceId,
            'user_id' => $userId,
            'was_last_member' => $wasLastMember,
        ]);
    }

    public function sharedSpaceMemberPromotedToAdmin(string $sharedSpaceId, string $userId): void
    {
        $this->log(
            'Shared space member promoted to admin',
            self::SHARED_SPACE_MEMBER_PROMOTED_TO_ADMIN,
            [],
            ['shared_space_id' => $sharedSpaceId, 'user_id' => $userId],
        );
    }

    public function sharedSpaceMemberDemotedFromAdmin(string $sharedSpaceId, string $userId): void
    {
        $this->log(
            'Shared space member demoted from admin',
            self::SHARED_SPACE_MEMBER_DEMOTED_FROM_ADMIN,
            [],
            ['shared_space_id' => $sharedSpaceId, 'user_id' => $userId],
        );
    }

    public function sharedSpaceMemberSuspended(string $sharedSpaceId, string $userId): void
    {
        $this->log(
            'Shared space member suspended',
            self::SHARED_SPACE_MEMBER_SUSPENDED,
            [],
            ['shared_space_id' => $sharedSpaceId, 'user_id' => $userId],
        );
    }

    public function sharedSpaceMemberActivated(string $sharedSpaceId, string $userId): void
    {
        $this->log(
            'Shared space member activated',
            self::SHARED_SPACE_MEMBER_ACTIVATED,
            [],
            ['shared_space_id' => $sharedSpaceId, 'user_id' => $userId],
        );
    }

    public function sharedSpaceMemberInviteRevoked(string $sharedSpaceId, int $inviteId, string $revokedByUserId): void
    {
        $this->log('Shared space invite revoked', self::SHARED_SPACE_MEMBER_INVITE_REVOKED, [], [
            'shared_space_id' => $sharedSpaceId,
            'invite_id' => $inviteId,
            'revoked_by' => $revokedByUserId,
        ]);
    }

    public function sharedSpaceMemberInvited(
        bool $isAdmin,
        string $sharedSpaceId,
        int $inviteId,
        string $invitedByUserId,
    ): void {
        $this->log('Shared space member invited', self::SHARED_SPACE_MEMBER_INVITED, [
            'IsAdmin' => $isAdmin ? 'true' : 'false',
        ], [
            'shared_space_id' => $sharedSpaceId,
            'invite_id' => $inviteId,
            'invited_by' => $invitedByUserId,
            'is_admin' => $isAdmin,
        ]);
    }

    /**
     * @param ?string $addedByUserId The member who added them, or null when they joined with an invite code
     */
    public function sharedSpaceMemberJoined(
        bool $isAdmin,
        string $sharedSpaceId,
        string $userId,
        int $lpasMoved,
        ?string $addedByUserId = null,
    ): void {
        $context = [
            'shared_space_id' => $sharedSpaceId,
            'user_id' => $userId,
            'lpas_moved' => $lpasMoved,
        ];

        if ($addedByUserId !== null) {
            $context['added_by_user_id'] = $addedByUserId;
        }

        $this->log('User joined shared space', self::SHARED_SPACE_MEMBER_JOINED, [
            'IsAdmin' => $isAdmin ? 'true' : 'false',
        ], $context);
    }

    /**
     * @param array<string, string> $dimensions
     * @param array<string, mixed> $context
     */
    private function log(
        string $message,
        string $event,
        array $dimensions,
        array $context,
        string $level = LogLevel::INFO,
    ): void {
        $this->logger->log($level, $message, [
                'event' => $event,
                self::CONTEXT_DIMENSIONS_KEY => $dimensions,
            ] + $context);
    }

    public static function isEvent(mixed $name): bool
    {
        return is_string($name)
            && str_starts_with($name, 'event.')
            && in_array($name, new \ReflectionClass(self::class)->getConstants(), true);
    }

    /**
     * The required fields that make a log line a CloudWatch Embedded Metric Format (EMF) record.
     *
     * @see https://docs.aws.amazon.com/AmazonCloudWatch/latest/monitoring/CloudWatch_Embedded_Metric_Format.html
     *
     * @param array<string, string> $dimensions Extra dimensions to split the metric by, as name => value
     *     (e.g. 'IsAdmin' => 'true'). Values must be low-cardinality strings (e.g. values with few possible values,
     *     never IDs), as every distinct value creates a separate billable metric.
     * @return array<string, mixed>
     */
    public static function emfFields(string $event, array $dimensions, int $timestampMs): array
    {
        $names = array_keys($dimensions);
        $dimensionSets = [['Event']];

        foreach ($names as $name) {
            $dimensionSets[] = ['Event', $name];
        }

        return [
                '_aws' => [
                    'Timestamp' => $timestampMs,
                    'CloudWatchMetrics' => [[
                        'Namespace' => 'opg-lpa',
                        'Dimensions' => $dimensionSets,
                        'Metrics' => [['Name' => 'EventCount', 'Unit' => 'Count']],
                    ]],
                ],
                'EventCount' => 1,
                'Event' => $event,
            ] + $dimensions;
    }
}
