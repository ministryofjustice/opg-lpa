<?php

namespace Application\Model\Service\AccountCleanup;

use Application\Feature;
use Application\Model\DataAccess\Postgres\UserModel;
use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\NotifyException;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\SharedSpace\SharedSpaceService;
use Application\Model\Service\Users\Service as UsersService;
use DateTime;
use Exception;
use MakeShared\Logging\LoggerTrait;

/**
 * - Deletes accounts after 9 months.
 * - Sends warning after 8 months and (9 months - 1 week).
 * - Delete accounts not activated after 24 hours.
 *
 * Warnings are sent by calling a HTTP based notification endpoint.
 * The auth service is not concerned with how that notification is then processed.
 */
class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use UserRepositoryTrait;
    use LoggerTrait;

    /**
     * GOV Notify template ID
     */
    public const CLEANUP_NOTIFICATION_TEMPLATE = '1acdd1fa-b463-4dac-847b-299e0ba3acb6';

    private array $config;
    private NotifyClient $notifyClient;
    private UsersService $usersService;
    private SharedSpaceService $sharedSpaceService;

    /**
     * Warning type constants
     */
    public const string WARNING_1_WEEK_NOTICE = '1-week-notice';
    public const string WARNING_1_MONTH_NOTICE = '1-month-notice';

    /**
     * Warning emails config
     */
    private array $warningEmailConfig = [
        self::WARNING_1_WEEK_NOTICE => [
            'dateShift'                  => '-9 months +1 week',
            'preOneLoginTemplateId'      => '3e0cc4c8-0c2a-4d2a-808a-32407b2e6276',
            'claimedAccountTemplateId'   => '399f2d80-9d3a-44c3-aa2e-8895d98b58fa',
            'unclaimedAccountTemplateId' => '1704ba7b-ded7-4664-abf0-6ef6f4343ad6',
            'sharedSpaceTemplateId'      => '84c0ea7d-1c18-4032-bf17-ee482a536d6e',
        ],
        self::WARNING_1_MONTH_NOTICE => [
            'dateShift'                  => '-8 months',
            'preOneLoginTemplateId'      => '0ef97354-9db2-4d52-a1cf-0aa762444cb1',
            'claimedAccountTemplateId'   => '1ccd99de-2067-457a-87de-cf5dabd13fff',
            'unclaimedAccountTemplateId' => 'e6a5d415-8198-4c07-9dcb-81a039b812e7',
            'sharedSpaceTemplateId'      => '59b4ea4b-c10b-4788-b550-1ff20a43c454',
        ],
    ];

    /**
     * Execute the account cleanup
     *
     * @param bool $dryRun When true, nothing is deleted, flagged or sent; the actions that
     *                     would have been taken are logged instead.
     */
    public function cleanup(bool $dryRun = false): int
    {
        //  Delete inactive accounts
        $expiredAccountsDeletedCount = $this->deleteExpiredAccounts($dryRun);

        //  Account inactive for 8 months and 3 weeks (1 week remaining)...
        $expiryAccountsWarning1WeekCount = $this->sendWarningEmails(self::WARNING_1_WEEK_NOTICE, $dryRun);

        //  Account inactive for 8 months (1 month remaining)...
        $expiryAccountsWarning1MonthCount = $this->sendWarningEmails(self::WARNING_1_MONTH_NOTICE, $dryRun);

        //  Remove accounts that have not been activated
        $unactivatedAccountsDeletedCount = $this->deleteUnactivatedAccounts($dryRun);

        $result = 0;

        // Send Account Cleanup notifications to site admins.
        if (
            !$dryRun &&
            isset($this->config['admin']['account_cleanup_notification_recipients']) &&
            is_array($this->config['admin']['account_cleanup_notification_recipients'])
        ) {
            foreach ($this->config['admin']['account_cleanup_notification_recipients'] as $recipient) {
                try {
                    $this->notifyClient->sendEmail($recipient, self::CLEANUP_NOTIFICATION_TEMPLATE, [
                        'stack'                             => $this->config['stack']['name'],
                        'expiredAccountsDeletedCount'       => $expiredAccountsDeletedCount,
                        'expiryAccountsWarning1WeekCount'   => $expiryAccountsWarning1WeekCount,
                        'expiryAccountsWarning1MonthCount'  => $expiryAccountsWarning1MonthCount,
                        'unactivatedAccountsDeletedCount'   => $unactivatedAccountsDeletedCount,
                    ]);
                } catch (NotifyException $e) {
                    // Other types of exception are worse; things still might not work tomorrow.
                    $this->getLogger()->alert('Unable to send admin notification message', [
                        'exception' => $e,
                    ]);

                    // error occurred
                    $result = 1;
                }
            }
        }

        $this->getLogger()->info($dryRun ? 'Account cleanup dry run completed' : 'Account cleanup completed', [
            'dryRun'                            => $dryRun,
            'expiredAccountsDeletedCount'       => $expiredAccountsDeletedCount,
            'expiryAccountsWarning1WeekCount'   => $expiryAccountsWarning1WeekCount,
            'expiryAccountsWarning1MonthCount'  => $expiryAccountsWarning1MonthCount,
            'unactivatedAccountsDeletedCount'   => $unactivatedAccountsDeletedCount,
        ]);

        return $result;
    }

    /**
     * Pulls back a list of all users who have no logged in for x time and sends them a notification
     * warning when their account will be deleted.
     *
     * @param $warningType: '1-week-notice', '1-month-notice'
     * @param bool $dryRun Log the email that would be sent, without sending it or flagging the account
     *
     * @return int The number of users notified
     *
     * @throws Exception
     *
     * @psalm-param '1-month-notice'|'1-week-notice' $warningType
     */
    private function sendWarningEmails(string $warningType, bool $dryRun = false)
    {
        if (!array_key_exists($warningType, $this->warningEmailConfig)) {
            throw new Exception('Invalid warning type: ' . $warningType);
        }

        $lastLoginBefore = new DateTime($this->warningEmailConfig[$warningType]['dateShift']);

        // Pull back a list of accounts...
        $iterator = $this->getUserRepository()->getAccountsInactiveSince($lastLoginBefore, $warningType);

        $counter = 0;

        foreach ($iterator as $user) {
            $templateId = $this->selectTemplate($warningType, $user);

            // Tell users the day before, giving them that full day to login.
            $notificationDate = $user->lastLoginAt()->add(\DateInterval::createFromDateString('+9 months'));

            try {
                $personalisations = $this->buildPersonalisations($user, $notificationDate);

                if ($dryRun) {
                    $this->getLogger()->info('Dry run: would send account expiry notification', [
                        'warning-type' => $warningType,
                        'template-id' => $templateId,
                        'user-id' => $user->id(),
                        'personalisations' => $personalisations,
                    ]);
                    $counter++;
                    continue;
                }

                // Call the notify to send the reminder email - this will thrown an exception on any errors
                $this->notifyClient->sendEmail($user->contactEmail(), $templateId, $personalisations);

                // Flag the account as 'notification sent'...
                $this->getUserRepository()->setInactivityFlag($user->id(), $warningType);

                $counter++;
            } catch (NotifyException $e) {
                // Notify exceptions aren't too bad, we will just retry tomorrow.
                $this->getLogger()->warning('Unable to send account expiry notification', [
                    'exception' => $e,
                    'template-id' => $templateId,
                    'user-id' => $user->id()
                ]);
            } catch (Exception $e) {
                $this->getLogger()->alert('Unable to send account expiry notification', [
                    'exception' => $e,
                    'template-id' => $templateId,
                    'user-id' => $user->id()
                ]);
            }
        }

        return $counter;
    }

    private function buildPersonalisations(UserModel $user, DateTime $notificationDate): array
    {
        $personalisations = ['deletionDate' => $notificationDate->format('j F Y')];

        if ($user->isActiveInSharedSpace()) {
            $personalisations['sharedSpaceName'] = $user->sharedSpaceName();
            $personalisations['isOnlyAdmin'] = $user->isSharedSpaceAdmin()
                && $this->sharedSpaceService->hasExactlyOneActiveAdmin($user->sharedSpaceId());
        }

        return $personalisations;
    }

    private function selectTemplate(string $warningType, UserModel $user): string
    {
        $warningConfig = $this->warningEmailConfig[$warningType];

        if ($user->isActiveInSharedSpace()) {
            return $warningConfig['sharedSpaceTemplateId'];
        }

        if (Feature::OneLogin->isEnabled()) {
            return $user->oneLoginEmail() ? $warningConfig['claimedAccountTemplateId'] : $warningConfig['unclaimedAccountTemplateId'];
        }

        return $warningConfig['preOneLoginTemplateId'];
    }

    /**
     * Delete all accounts that have expired.
     *
     * @return int The number of accounts deleted
     */
    private function deleteExpiredAccounts(bool $dryRun = false)
    {
        $lastLoginBefore = new DateTime('-9 months');

        // Pull back a list of accounts...
        $iterator = $this->getUserRepository()->getAccountsInactiveSince($lastLoginBefore);

        //---

        $counter = 0;

        foreach ($iterator as $user) {
            if ($dryRun) {
                $this->getLogger()->info('Dry run: would delete expired account', ['user-id' => $user->id()]);
            } else {
                //  Delete the user - this will also delete any LPAs
                $this->usersService->delete($user->id(), 'expired');
            }

            $counter++;
        }

        return $counter;
    }

    /**
     * Delete all accounts created before time x that have not yet been activated.
     *
     * @return int The number of accounts deleted
     */
    private function deleteUnactivatedAccounts(bool $dryRun = false)
    {
        $unactivatedSince = new DateTime('-24 hours');

        // Pull back a list of accounts...
        $iterator = $this->getUserRepository()->getAccountsUnactivatedOlderThan($unactivatedSince);

        //---

        $counter = 0;

        foreach ($iterator as $user) {
            if ($dryRun) {
                $this->getLogger()->info('Dry run: would delete unactivated account', ['user-id' => $user->id()]);
            } else {
                //  Delete the user account
                $this->usersService->delete($user->id(), 'unactivated');
            }

            $counter++;
        }

        return $counter;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setNotifyClient(NotifyClient $notifyClient): void
    {
        $this->notifyClient = $notifyClient;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setUsersService(UsersService $usersService): void
    {
        $this->usersService = $usersService;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function setSharedSpaceService(SharedSpaceService $sharedSpaceService): void
    {
        $this->sharedSpaceService = $sharedSpaceService;
    }
}
