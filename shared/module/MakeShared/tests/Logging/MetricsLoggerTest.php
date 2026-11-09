<?php

declare(strict_types=1);

namespace MakeSharedTest\Logging;

use MakeShared\Logging\MetricsLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MetricsLoggerTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private MetricsLogger $metrics;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->metrics = new MetricsLogger($this->logger);
    }

    /**
     * @param array<string, string> $dimensions
     * @param array<string, mixed> $context
     */
    private function expectLog(
        string $level,
        string $message,
        string $event,
        array $dimensions = [],
        array $context = [],
    ): void {
        $this->logger->expects($this->once())->method('log')->with(
            $level,
            $message,
            ['event' => $event, 'metric_dimensions' => $dimensions] + $context,
        );
    }

    public function testIsEventAcceptsKnownEvents()
    {
        $this->assertTrue(MetricsLogger::isEvent(MetricsLogger::SHARED_SPACE_CREATED));
        $this->assertTrue(MetricsLogger::isEvent('event.user.logged_in'));
    }

    #[DataProvider('nonEventProvider')]
    public function testIsEventRejectsNonEvents(mixed $value)
    {
        $this->assertFalse(MetricsLogger::isEvent($value));
    }

    public static function nonEventProvider(): array
    {
        return [
            'unknown event' => ['event.not.a.metric'],
            'old style name' => ['shared_space.joined'],
            'other constant' => [MetricsLogger::CONTEXT_DIMENSIONS_KEY],
            'null' => [null],
            'array' => [['event.user.logged_in']],
        ];
    }

    public function testEmfFieldsWithoutDimensions()
    {
        $fields = MetricsLogger::emfFields(MetricsLogger::SHARED_SPACE_CREATED, [], 1744820717123);

        $this->assertSame(1744820717123, $fields['_aws']['Timestamp']);
        $this->assertSame([['Event']], $fields['_aws']['CloudWatchMetrics'][0]['Dimensions']);
        $this->assertSame('opg-lpa', $fields['_aws']['CloudWatchMetrics'][0]['Namespace']);
        $this->assertSame(1, $fields['EventCount']);
        $this->assertSame('event.shared_space.created', $fields['Event']);
    }

    public function testEmfFieldsWithDimensions()
    {
        $fields = MetricsLogger::emfFields(MetricsLogger::SHARED_SPACE_MEMBER_INVITED, ['IsAdmin' => 'true'], 1);

        $this->assertSame(
            [['Event'], ['Event', 'IsAdmin']],
            $fields['_aws']['CloudWatchMetrics'][0]['Dimensions'],
        );
        $this->assertSame('true', $fields['IsAdmin']);
    }

    public function testUserCreated()
    {
        $this->expectLog('info', 'User created', 'event.user.created', [], ['user_id' => 'u1']);

        $this->metrics->userCreated('u1');
    }

    public function testUserFailedToLoginDefaultsToWarning()
    {
        $this->expectLog('warning', 'User failed to login', 'event.user.failed_to_login', [
            'Reason' => 'state_mismatch',
        ]);

        $this->metrics->userFailedToLogin('state_mismatch');
    }

    public function testUserFailedToLoginWithContextAndLevel()
    {
        $this->expectLog('error', 'User failed to login', 'event.user.failed_to_login', [
            'Reason' => 'token_exchange_failed',
        ], ['message' => 'boom']);

        $this->metrics->userFailedToLogin('token_exchange_failed', ['message' => 'boom'], 'error');
    }

    public function testUserIdentifiedAsLay()
    {
        $this->expectLog('info', 'User identified as lay', 'event.user.identified_as_lay', [], ['user_id' => 'u1']);

        $this->metrics->userIdentifiedAsLay('u1');
    }

    public function testUserIdentifiedAsProfessional()
    {
        $this->expectLog(
            'info',
            'User identified as professional',
            'event.user.identified_as_professional',
            [],
            ['user_id' => 'u1'],
        );

        $this->metrics->userIdentifiedAsProfessional('u1');
    }

    public function testUserLinkedExistingMakeAccount()
    {
        $this->expectLog(
            'info',
            'User linked existing Make account',
            'event.user.linked_existing_make_account',
            [],
            ['user_id' => 'u1'],
        );

        $this->metrics->userLinkedExistingMakeAccount('u1');
    }

    public function testUserLoggedIn()
    {
        $this->expectLog('info', 'User logged in', 'event.user.logged_in', [], ['user_id' => 'u1']);

        $this->metrics->userLoggedIn('u1');
    }

    public function testUserLoggedOut()
    {
        $this->expectLog('info', 'User logged out', 'event.user.logged_out');

        $this->metrics->userLoggedOut();
    }

    public function testUserSessionEndedByOneLogin()
    {
        $this->expectLog(
            'info',
            'User session ended by One Login',
            'event.user.session_ended_by_one_login',
            [],
            ['user_id' => 'u1'],
        );

        $this->metrics->userSessionEndedByOneLogin('u1');
    }

    public function testUserRefusedPasswordReset()
    {
        $this->expectLog(
            'info',
            'Password reset refused for a One Login account',
            'event.user.refused_password_reset',
        );

        $this->metrics->userRefusedPasswordReset();
    }

    public function testUserResetLegacyAccountPassword()
    {
        $this->expectLog(
            'info',
            'User successfully changed their password via a reset',
            'event.user.reset_legacy_account_password',
        );

        $this->metrics->userResetLegacyAccountPassword();
    }

    public function testUserFailedToLogoutDefaultsToInfo()
    {
        $this->expectLog('info', 'User failed to logout', 'event.user.failed_to_logout', [
            'Reason' => 'invalid_token',
        ]);

        $this->metrics->userFailedToLogout('invalid_token');
    }

    public function testUserFailedToLogoutWithContextAndLevel()
    {
        $this->expectLog('error', 'User failed to logout', 'event.user.failed_to_logout', [
            'Reason' => 'backchannel_logout_failed',
        ], ['message' => 'boom']);

        $this->metrics->userFailedToLogout('backchannel_logout_failed', ['message' => 'boom'], 'error');
    }

    public function testUserFailedToLinkExistingMakeAccount()
    {
        $this->expectLog(
            'info',
            'User failed to link existing Make account',
            'event.user.failed_to_link_existing_make_account',
            ['Reason' => 'account_locked'],
        );

        $this->metrics->userFailedToLinkExistingMakeAccount('account_locked');
    }

    public function testUserDeleted()
    {
        $this->expectLog('info', 'User deleted', 'event.user.deleted', ['Reason' => 'expired'], ['user_id' => 'u1']);

        $this->metrics->userDeleted('expired', 'u1');
    }

    public function testSharedSpaceCreated()
    {
        $this->expectLog('info', 'Shared space created', 'event.shared_space.created', [], [
            'shared_space_id' => 'abc',
            'lpas_moved' => 3,
        ]);

        $this->metrics->sharedSpaceCreated('abc', 3);
    }

    public function testSharedSpaceDeleted()
    {
        $this->expectLog('info', 'Shared space deleted', 'event.shared_space.deleted', [], [
            'shared_space_id' => 'abc',
            'deleted_by_user_id' => 'u1',
            'lpas_deleted' => 5,
        ]);

        $this->metrics->sharedSpaceDeleted('abc', 'u1', 5);
    }

    public function testSharedSpaceMemberFailedToJoin()
    {
        $this->expectLog(
            'info',
            'User failed to join shared space',
            'event.shared_space.member.failed_to_join',
            ['Reason' => 'invite_expired'],
            ['user_id' => 'u1'],
        );

        $this->metrics->sharedSpaceMemberFailedToJoin('invite_expired', 'u1');
    }

    public function testSharedSpaceMemberDeletedByAdmin()
    {
        $this->expectLog(
            'info',
            'Shared space member deleted',
            'event.shared_space.member.deleted',
            ['DeletedBy' => 'admin'],
            ['shared_space_id' => 'abc', 'deleted_by_user_id' => 'u2', 'deleted_user_id' => 'u1'],
        );

        $this->metrics->sharedSpaceMemberDeletedByAdmin('abc', 'u2', 'u1');
    }

    #[DataProvider('booleanProvider')]
    public function testSharedSpaceMemberDeletedBySelf(bool $wasLastMember)
    {
        $this->expectLog(
            'info',
            'Shared space member deleted',
            'event.shared_space.member.deleted',
            ['DeletedBy' => 'self'],
            ['shared_space_id' => 'abc', 'user_id' => 'u1', 'was_last_member' => $wasLastMember],
        );

        $this->metrics->sharedSpaceMemberDeletedBySelf('abc', 'u1', $wasLastMember);
    }

    public function testSharedSpaceMemberPromotedToAdmin()
    {
        $this->expectLog(
            'info',
            'Shared space member promoted to admin',
            'event.shared_space.member.promoted_to_admin',
            [],
            ['shared_space_id' => 'abc', 'user_id' => 'u1'],
        );

        $this->metrics->sharedSpaceMemberPromotedToAdmin('abc', 'u1');
    }

    public function testSharedSpaceMemberDemotedFromAdmin()
    {
        $this->expectLog(
            'info',
            'Shared space member demoted from admin',
            'event.shared_space.member.demoted_from_admin',
            [],
            ['shared_space_id' => 'abc', 'user_id' => 'u1'],
        );

        $this->metrics->sharedSpaceMemberDemotedFromAdmin('abc', 'u1');
    }

    public function testSharedSpaceMemberSuspended()
    {
        $this->expectLog(
            'info',
            'Shared space member suspended',
            'event.shared_space.member.suspended',
            [],
            ['shared_space_id' => 'abc', 'user_id' => 'u1'],
        );

        $this->metrics->sharedSpaceMemberSuspended('abc', 'u1');
    }

    public function testSharedSpaceMemberActivated()
    {
        $this->expectLog(
            'info',
            'Shared space member activated',
            'event.shared_space.member.activated',
            [],
            ['shared_space_id' => 'abc', 'user_id' => 'u1'],
        );

        $this->metrics->sharedSpaceMemberActivated('abc', 'u1');
    }

    public function testSharedSpaceMemberInviteRevoked()
    {
        $this->expectLog(
            'info',
            'Shared space invite revoked',
            'event.shared_space.member.invite_revoked',
            [],
            ['shared_space_id' => 'abc', 'invite_id' => 5, 'revoked_by' => 'u1'],
        );

        $this->metrics->sharedSpaceMemberInviteRevoked('abc', 5, 'u1');
    }

    #[DataProvider('isAdminProvider')]
    public function testSharedSpaceMemberInvited(bool $isAdmin, string $expected)
    {
        $this->expectLog(
            'info',
            'Shared space member invited',
            'event.shared_space.member.invited',
            ['IsAdmin' => $expected],
            ['shared_space_id' => 'abc', 'invite_id' => 7, 'invited_by' => 'u1', 'is_admin' => $isAdmin],
        );

        $this->metrics->sharedSpaceMemberInvited($isAdmin, 'abc', 7, 'u1');
    }

    #[DataProvider('isAdminProvider')]
    public function testSharedSpaceMemberJoinedWithInviteCode(bool $isAdmin, string $expected)
    {
        $this->expectLog(
            'info',
            'User joined shared space',
            'event.shared_space.member.joined',
            ['IsAdmin' => $expected],
            ['shared_space_id' => 'abc', 'user_id' => 'u1', 'lpas_moved' => 2],
        );

        $this->metrics->sharedSpaceMemberJoined($isAdmin, 'abc', 'u1', 2);
    }

    #[DataProvider('isAdminProvider')]
    public function testSharedSpaceMemberJoinedAddedByMember(bool $isAdmin, string $expected)
    {
        $this->expectLog(
            'info',
            'User joined shared space',
            'event.shared_space.member.joined',
            ['IsAdmin' => $expected],
            ['shared_space_id' => 'abc', 'user_id' => 'u1', 'lpas_moved' => 0, 'added_by_user_id' => 'u2'],
        );

        $this->metrics->sharedSpaceMemberJoined($isAdmin, 'abc', 'u1', 0, 'u2');
    }

    public static function isAdminProvider(): array
    {
        return [
            'admin' => [true, 'true'],
            'non-admin' => [false, 'false'],
        ];
    }

    public static function booleanProvider(): array
    {
        return [
            'last member' => [true],
            'not last member' => [false],
        ];
    }
}
