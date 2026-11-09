<?php

declare(strict_types=1);

namespace MakeShared\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Metrics
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

    public static function isEvent(mixed $name): bool
    {
        return is_string($name)
            && str_starts_with($name, 'event.')
            && in_array($name, new \ReflectionClass(self::class)->getConstants(), true);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function userCreated(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'User created', self::USER_CREATED, [], $context);
    }

    /**
     * @param string $reason Why the login failed, e.g. state_mismatch, token_exchange_failed
     * @param array<string, mixed> $context Extra log fields
     * @param string $level A LogLevel constant. Use ERROR for unexpected failures, WARNING for expected ones
     */
    public static function userFailedToLogin(
        LoggerInterface $logger,
        string $reason,
        array $context = [],
        string $level = LogLevel::WARNING,
    ): void {
        self::log(
            $logger,
            'User failed to login',
            self::USER_FAILED_TO_LOGIN,
            ['Reason' => $reason],
            $context,
            $level,
        );
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function userIdentifiedAsLay(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'User identified as lay', self::USER_IDENTIFIED_AS_LAY, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function userIdentifiedAsProfessional(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'User identified as professional', self::USER_IDENTIFIED_AS_PROFESSIONAL, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function userLinkedExistingMakeAccount(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'User linked existing Make account', self::USER_LINKED_EXISTING_MAKE_ACCOUNT, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function userLoggedIn(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'User logged in', self::USER_LOGGED_IN, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function userLoggedOut(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'User logged out', self::USER_LOGGED_OUT, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function userSessionEndedByOneLogin(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'User session ended by One Login', self::USER_SESSION_ENDED_BY_ONE_LOGIN, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function userRefusedPasswordReset(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'Password reset refused for a One Login account', self::USER_REFUSED_PASSWORD_RESET, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function userResetLegacyAccountPassword(LoggerInterface $logger, array $context = []): void
    {
        self::log(
            $logger,
            'User successfully changed their password via a reset',
            self::USER_RESET_LEGACY_ACCOUNT_PASSWORD,
            [],
            $context,
        );
    }

    /**
     * @param string $reason Why the logout failed, e.g. invalid_token, replayed_jti
     * @param array<string, mixed> $context Extra log fields
     * @param string $level A LogLevel constant. Use ERROR for unexpected failures, WARNING for expected ones
     */
    public static function userFailedToLogout(
        LoggerInterface $logger,
        string $reason,
        array $context = [],
        string $level = LogLevel::INFO,
    ): void {
        self::log($logger, 'User failed to logout', self::USER_FAILED_TO_LOGOUT, ['Reason' => $reason], $context, $level);
    }

    /**
     * @param string $reason Why the link was rejected, e.g. a LinkReason constant
     * @param array<string, mixed> $context Extra log fields
     */
    public static function userFailedToLinkExistingMakeAccount(
        LoggerInterface $logger,
        string $reason,
        array $context = [],
    ): void {
        self::log(
            $logger,
            'User failed to link existing Make account',
            self::USER_FAILED_TO_LINK_EXISTING_MAKE_ACCOUNT,
            ['Reason' => $reason],
            $context,
        );
    }

    /**
     * @param string $reason Why the account was deleted, e.g. user-initiated, expired, unactivated
     * @param array<string, mixed> $context Extra log fields
     */
    public static function userDeleted(LoggerInterface $logger, string $reason, array $context = []): void
    {
        self::log($logger, 'User deleted', self::USER_DELETED, ['Reason' => $reason], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function sharedSpaceCreated(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'Shared space created', self::SHARED_SPACE_CREATED, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function sharedSpaceDeleted(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'Shared space deleted', self::SHARED_SPACE_DELETED, [], $context);
    }

    /**
     * @param string $reason Low-cardinality failure category, used as the "Reason" dimension
     * @param array<string, mixed> $context Extra log fields
     */
    public static function sharedSpaceMemberFailedToJoin(
        LoggerInterface $logger,
        string $reason,
        array $context = [],
    ): void {
        self::log($logger, 'User failed to join shared space', self::SHARED_SPACE_MEMBER_FAILED_TO_JOIN, [
            'Reason' => $reason,
        ], $context);
    }

    /**
     * @param string $deletedBy 'admin' or 'self'
     * @param array<string, mixed> $context Extra log fields
     */
    public static function sharedSpaceMemberDeleted(LoggerInterface $logger, string $deletedBy, array $context = []): void
    {
        self::log($logger, 'Shared space member deleted', self::SHARED_SPACE_MEMBER_DELETED, ['DeletedBy' => $deletedBy], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function sharedSpaceMemberPromotedToAdmin(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'Shared space member promoted to admin', self::SHARED_SPACE_MEMBER_PROMOTED_TO_ADMIN, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function sharedSpaceMemberDemotedFromAdmin(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'Shared space member demoted from admin', self::SHARED_SPACE_MEMBER_DEMOTED_FROM_ADMIN, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function sharedSpaceMemberSuspended(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'Shared space member suspended', self::SHARED_SPACE_MEMBER_SUSPENDED, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function sharedSpaceMemberActivated(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'Shared space member activated', self::SHARED_SPACE_MEMBER_ACTIVATED, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function sharedSpaceMemberInviteRevoked(LoggerInterface $logger, array $context = []): void
    {
        self::log($logger, 'Shared space invite revoked', self::SHARED_SPACE_MEMBER_INVITE_REVOKED, [], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function sharedSpaceMemberInvited(LoggerInterface $logger, bool $isAdmin, array $context = []): void
    {
        self::log($logger, 'Shared space member invited', self::SHARED_SPACE_MEMBER_INVITED, [
            'IsAdmin' => $isAdmin ? 'true' : 'false',
        ], $context);
    }

    /**
     * @param array<string, mixed> $context Extra log fields
     */
    public static function sharedSpaceMemberJoined(LoggerInterface $logger, bool $isAdmin, array $context = []): void
    {
        self::log($logger, 'User joined shared space', self::SHARED_SPACE_MEMBER_JOINED, [
            'IsAdmin' => $isAdmin ? 'true' : 'false',
        ], $context);
    }

    /**
     * @param array<string, string> $dimensions
     * @param array<string, mixed> $context
     */
    private static function log(
        LoggerInterface $logger,
        string $message,
        string $event,
        array $dimensions,
        array $context,
        string $level = LogLevel::INFO,
    ): void {
        $logger->log($level, $message, [
            'event' => $event,
            self::CONTEXT_DIMENSIONS_KEY => $dimensions,
        ] + $context);
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
