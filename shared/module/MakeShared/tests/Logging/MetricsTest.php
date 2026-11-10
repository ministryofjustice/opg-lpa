<?php

declare(strict_types=1);

namespace MakeSharedTest\Logging;

use MakeShared\Logging\MetricRecorderInterface;
use MakeShared\Logging\Metrics;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MetricsTest extends TestCase
{
    protected function tearDown(): void
    {
        Metrics::setRecorder(null);
    }

    public function testUserCreatedRecordsOtelMetric()
    {
        $recorder = $this->createMock(MetricRecorderInterface::class);
        $recorder->expects($this->once())->method('record')->with('event.user.created', []);
        Metrics::setRecorder($recorder);

        Metrics::userCreated($this->createStub(LoggerInterface::class));
    }

    public function testUserCreatedWithoutRecorderOnlyLogs()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log');

        Metrics::userCreated($logger);
    }

    public function testIsEventAcceptsKnownEvents()
    {
        $this->assertTrue(Metrics::isEvent(Metrics::SHARED_SPACE_CREATED));
        $this->assertTrue(Metrics::isEvent('event.user.logged_in'));
    }

    #[DataProvider('nonEventProvider')]
    public function testIsEventRejectsNonEvents(mixed $value)
    {
        $this->assertFalse(Metrics::isEvent($value));
    }

    public static function nonEventProvider(): array
    {
        return [
            'unknown event' => ['event.not.a.metric'],
            'old style name' => ['shared_space.joined'],
            'other constant' => [Metrics::CONTEXT_DIMENSIONS_KEY],
            'null' => [null],
            'array' => [['event.user.logged_in']],
        ];
    }

    public function testEmfFieldsWithoutDimensions()
    {
        $fields = Metrics::emfFields(Metrics::SHARED_SPACE_CREATED, [], 1744820717123);

        $this->assertSame(1744820717123, $fields['_aws']['Timestamp']);
        $this->assertSame([['Event']], $fields['_aws']['CloudWatchMetrics'][0]['Dimensions']);
        $this->assertSame('opg-lpa', $fields['_aws']['CloudWatchMetrics'][0]['Namespace']);
        $this->assertSame(1, $fields['EventCount']);
        $this->assertSame('event.shared_space.created', $fields['Event']);
    }

    public function testEmfFieldsWithDimensions()
    {
        $fields = Metrics::emfFields(Metrics::SHARED_SPACE_MEMBER_INVITED, ['IsAdmin' => 'true'], 1);

        $this->assertSame(
            [['Event'], ['Event', 'IsAdmin']],
            $fields['_aws']['CloudWatchMetrics'][0]['Dimensions'],
        );
        $this->assertSame('true', $fields['IsAdmin']);
    }

    public function testUserCreatedLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User created',
            [
                'event' => 'event.user.created',
                'metric_dimensions' => [],
                'user_id' => 'u1',
            ],
        );

        Metrics::userCreated($logger, ['user_id' => 'u1']);
    }

    public function testUserFailedToLoginLogsEventWithReason()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'warning',
            'User failed to login',
            [
                'event' => 'event.user.failed_to_login',
                'metric_dimensions' => ['Reason' => 'state_mismatch'],
            ],
        );

        Metrics::userFailedToLogin($logger, 'state_mismatch');
    }

    public function testUserFailedToLogoutLogsEventWithReasonAtGivenLevel()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'error',
            'User failed to logout',
            [
                'event' => 'event.user.failed_to_logout',
                'metric_dimensions' => ['Reason' => 'backchannel_logout_failed'],
                'message' => 'boom',
            ],
        );

        Metrics::userFailedToLogout($logger, 'backchannel_logout_failed', ['message' => 'boom'], 'error');
    }

    public function testUserFailedToLinkExistingMakeAccountLogsEventWithReason()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User failed to link existing Make account',
            [
                'event' => 'event.user.failed_to_link_existing_make_account',
                'metric_dimensions' => ['Reason' => 'account_locked'],
            ],
        );

        Metrics::userFailedToLinkExistingMakeAccount($logger, 'account_locked');
    }

    public function testUserIdentifiedAsLayLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User identified as lay',
            ['event' => 'event.user.identified_as_lay', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::userIdentifiedAsLay($logger, ['user_id' => 'u1']);
    }

    public function testUserIdentifiedAsProfessionalLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User identified as professional',
            ['event' => 'event.user.identified_as_professional', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::userIdentifiedAsProfessional($logger, ['user_id' => 'u1']);
    }

    public function testUserLinkedExistingMakeAccountLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User linked existing Make account',
            ['event' => 'event.user.linked_existing_make_account', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::userLinkedExistingMakeAccount($logger, ['user_id' => 'u1']);
    }

    public function testUserLoggedInLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User logged in',
            ['event' => 'event.user.logged_in', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::userLoggedIn($logger, ['user_id' => 'u1']);
    }

    public function testUserLoggedOutLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User logged out',
            ['event' => 'event.user.logged_out', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::userLoggedOut($logger, ['user_id' => 'u1']);
    }

    public function testUserSessionEndedByOneLoginLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User session ended by One Login',
            ['event' => 'event.user.session_ended_by_one_login', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::userSessionEndedByOneLogin($logger, ['user_id' => 'u1']);
    }

    public function testUserRefusedPasswordResetLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'Password reset refused for a One Login account',
            ['event' => 'event.user.refused_password_reset', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::userRefusedPasswordReset($logger, ['user_id' => 'u1']);
    }

    public function testUserResetLegacyAccountPasswordLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User successfully changed their password via a reset',
            ['event' => 'event.user.reset_legacy_account_password', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::userResetLegacyAccountPassword($logger, ['user_id' => 'u1']);
    }

    public function testUserDeletedLogsEventWithReason()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User deleted',
            [
                'event' => 'event.user.deleted',
                'metric_dimensions' => ['Reason' => 'expired'],
                'user_id' => 'u1',
            ],
        );

        Metrics::userDeleted($logger, 'expired', ['user_id' => 'u1']);
    }

    public function testSharedSpaceCreatedLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'Shared space created',
            [
                'event' => 'event.shared_space.created',
                'metric_dimensions' => [],
                'shared_space_id' => 'abc',
            ],
        );

        Metrics::sharedSpaceCreated($logger, ['shared_space_id' => 'abc']);
    }

    public function testSharedSpaceCreatedLogsEventWithoutContext()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'Shared space created',
            ['event' => 'event.shared_space.created', 'metric_dimensions' => []],
        );

        Metrics::sharedSpaceCreated($logger);
    }

    public function testSharedSpaceMemberFailedToJoinLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User failed to join shared space',
            [
                'event' => 'event.shared_space.member.failed_to_join',
                'metric_dimensions' => ['Reason' => 'invite_expired'],
                'user_id' => 'u1',
            ],
        );

        Metrics::sharedSpaceMemberFailedToJoin($logger, 'invite_expired', ['user_id' => 'u1']);
    }

    public function testSharedSpaceMemberDeletedLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'Shared space member deleted',
            [
                'event' => 'event.shared_space.member.deleted',
                'metric_dimensions' => ['DeletedBy' => 'admin'],
                'deleted_user_id' => 'u1',
            ],
        );

        Metrics::sharedSpaceMemberDeleted($logger, 'admin', ['deleted_user_id' => 'u1']);
    }

    public function testSharedSpaceMemberPromotedToAdminLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'Shared space member promoted to admin',
            ['event' => 'event.shared_space.member.promoted_to_admin', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::sharedSpaceMemberPromotedToAdmin($logger, ['user_id' => 'u1']);
    }

    public function testSharedSpaceMemberDemotedFromAdminLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'Shared space member demoted from admin',
            ['event' => 'event.shared_space.member.demoted_from_admin', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::sharedSpaceMemberDemotedFromAdmin($logger, ['user_id' => 'u1']);
    }

    public function testSharedSpaceMemberSuspendedLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'Shared space member suspended',
            ['event' => 'event.shared_space.member.suspended', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::sharedSpaceMemberSuspended($logger, ['user_id' => 'u1']);
    }

    public function testSharedSpaceMemberActivatedLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'Shared space member activated',
            ['event' => 'event.shared_space.member.activated', 'metric_dimensions' => [], 'user_id' => 'u1'],
        );

        Metrics::sharedSpaceMemberActivated($logger, ['user_id' => 'u1']);
    }

    public function testSharedSpaceMemberInviteRevokedLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'Shared space invite revoked',
            [
                'event' => 'event.shared_space.member.invite_revoked',
                'metric_dimensions' => [],
                'invite_id' => 5,
            ],
        );

        Metrics::sharedSpaceMemberInviteRevoked($logger, ['invite_id' => 5]);
    }

    public function testSharedSpaceDeletedLogsEvent()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'Shared space deleted',
            [
                'event' => 'event.shared_space.deleted',
                'metric_dimensions' => [],
                'lpas_deleted' => 5,
            ],
        );

        Metrics::sharedSpaceDeleted($logger, ['lpas_deleted' => 5]);
    }

    #[DataProvider('isAdminProvider')]
    public function testSharedSpaceMemberInvitedLogsEvent(bool $isAdmin, string $expected)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'Shared space member invited',
            [
                'event' => 'event.shared_space.member.invited',
                'metric_dimensions' => ['IsAdmin' => $expected],
                'invite_id' => 7,
            ],
        );

        Metrics::sharedSpaceMemberInvited($logger, $isAdmin, ['invite_id' => 7]);
    }

    #[DataProvider('isAdminProvider')]
    public function testSharedSpaceMemberJoinedLogsEvent(bool $isAdmin, string $expected)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('log')->with(
            'info',
            'User joined shared space',
            [
                'event' => 'event.shared_space.member.joined',
                'metric_dimensions' => ['IsAdmin' => $expected],
            ],
        );

        Metrics::sharedSpaceMemberJoined($logger, $isAdmin);
    }

    public static function isAdminProvider(): array
    {
        return [
            'admin' => [true, 'true'],
            'non-admin' => [false, 'false'],
        ];
    }
}
