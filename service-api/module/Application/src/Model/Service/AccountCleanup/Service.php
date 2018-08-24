<?php

namespace Application\Model\Service\AccountCleanup;

use Alphagov\Notifications\Client as NotifyClient;
use Alphagov\Notifications\Exception\NotifyException;
use Application\Model\DataAccess\Repository\Auth\UserRepositoryTrait;
use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollectionTrait;
use Application\Model\DataAccess\Mongo\Collection\ApiUserCollectionTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\UserManagement\Service as UserManagementService;
use Aws\Sns\SnsClient;
use DateTime;
use Exception;

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
    use ApiLpaCollectionTrait;
    use ApiUserCollectionTrait;
    use UserRepositoryTrait;

    /**
     * @var array
     */
    private $config;

    /**
     * @var NotifyClient
     */
    private $notifyClient;

    /**
     * @var SnsClient
     */
    private $snsClient;

    /**
     * @var UserManagementService
     */
    private $userManagementService;

    /**
     * Warning type constants
     */
    const WARNING_1_WEEK_NOTICE = '1-week-notice';
    const WARNING_1_MONTH_NOTICE = '1-month-notice';

    /**
     * Warning emails config
     */
    private $warningEmailConfig = [
        self::WARNING_1_WEEK_NOTICE => [
            'dateShift'  => '-9 months +1 week',
            'templateId' => '3e0cc4c8-0c2a-4d2a-808a-32407b2e6276',
        ],
        self::WARNING_1_MONTH_NOTICE => [
            'dateShift'  => '-8 months',
            'templateId' => '0ef97354-9db2-4d52-a1cf-0aa762444cb1',
        ],
    ];

    /**
     * Execute the account cleanup
     */
    public function cleanup()
    {
        //  Delete inactive accounts
        $expiredAccountsDeletedCount = $this->deleteExpiredAccounts();

        //  Account inactive for 8 months and 3 weeks (1 week remaining)...
        $expiryAccountsWarning1WeekCount = $this->sendWarningEmails(self::WARNING_1_WEEK_NOTICE);

        //  Account inactive for 8 months (1 month remaining)...
        $expiryAccountsWarning1MonthCount = $this->sendWarningEmails(self::WARNING_1_MONTH_NOTICE);

        //  Remove accounts that have not been activated
        $unactivatedAccountsDeletedCount = $this->deleteUnactivatedAccounts();

        $message = "Unactivated accounts deleted: $unactivatedAccountsDeletedCount\n";
        $message .= "One month's notice emails sent: $expiryAccountsWarning1MonthCount\n";
        $message .= "One week's notice emails sent: $expiryAccountsWarning1WeekCount\n";
        $message .= "Expired accounts deleted: $expiredAccountsDeletedCount\n";
        $message .= "\nLove,\n" . $this->config['stack']['name'];

        try {
            $config = $this->config['log']['sns'];

            $this->snsClient->publish(array(
                'TopicArn' => $config['endpoints']['info'],
                'Message' => $message,
                'Subject' => 'LPA Account Cleanup Notification',
                'MessageStructure' => 'string',
            ));
        } catch (Exception $e) {
            $this->getLogger()->alert(
                'Unable to send AWS SNS notification',
                ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Pulls back a list of all users who have no logged in for x time and sends them a notification
     * warning when their account will be deleted.
     *
     * @param $warningType
     * @return int The number of users notified
     * @throws Exception
     */
    private function sendWarningEmails($warningType)
    {
        if (!array_key_exists($warningType, $this->warningEmailConfig)) {
            throw new Exception('Invalid warning type: ' . $warningType);
        }

        $warningConfig = $this->warningEmailConfig[$warningType];

        $lastLoginBefore = new DateTime($warningConfig['dateShift']);
        $templateId = $warningConfig['templateId'];

        echo "Sending {$warningType} warning notifications to accounts inactive since " . $lastLoginBefore->format('r') . "\n";

        // Pull back a list of accounts...
        $iterator = $this->getUserRepository()->getAccountsInactiveSince($lastLoginBefore, $warningType);

        $counter = 0;

        foreach ($iterator as $user) {
            // Tell users the day before, giving them that full day to login.
            $notificationDate = $user->lastLoginAt()->add(\DateInterval::createFromDateString('+9 months'));

            try {
                // Call the notify to send the reminder email - this will thrown an exception on any errors
                $this->notifyClient->sendEmail($user->username(), $templateId, [
                    'deletionDate' => $notificationDate->format('j F Y'),
                ]);

                // Flag the account as 'notification sent'...
                $this->getUserRepository()->setInactivityFlag($user->id(), $warningType);

                $counter++;
            } catch (NotifyException $e) {
                echo "NotifyException: " . $e->getMessage() . "\n";

                // Notify exceptions aren't too bad, we will just retry tomorrow.
                $this->getLogger()->warn('Unable to send account expiry notification', [
                    'exception' => $e->getMessage()
                ]);
            } catch (Exception $e) {
                echo "Exception: " . $e->getMessage() . "\n";

                // Other types of exception are worse; things still might not work tomorrow.
                $this->getLogger()->alert('Unable to send account expiry notification', [
                    'exception' => $e->getMessage()
                ]);
            }
        }

        echo "{$counter} notifications sent.\n";

        return $counter;
    }

    /**
     * Delete all accounts that have expired.
     *
     * @return int The number of accounts deleted
     */
    private function deleteExpiredAccounts()
    {
        $lastLoginBefore = new DateTime('-9 months');

        echo "Deleting accounts inactive since " . $lastLoginBefore->format('r') . "\n";

        // Pull back a list of accounts...
        $iterator = $this->getUserRepository()->getAccountsInactiveSince($lastLoginBefore);

        //---

        $counter = 0;

        foreach ($iterator as $user) {
            //  Delete the user data
            $this->userManagementService->delete($user->id(), 'expired');

            //  Delete the LPAs in the API data for this user
            $lpas = $this->apiLpaCollection->fetchByUserId($user->id());

            foreach ($lpas as $lpa) {
                $this->apiLpaCollection->deleteById($lpa['_id'], $lpa['user']);
            }

            $this->apiUserCollection->deleteById($user->id());

            $counter++;
        }

        echo "{$counter} accounts deleted.\n";

        return $counter;
    }

    /**
     * Delete all accounts created before time x that have not yet been activated.
     *
     * @return int The number of accounts deleted
     */
    private function deleteUnactivatedAccounts()
    {
        $unactivatedSince = new DateTime('-24 hours');

        echo "Deleting unactivated accounts created before " . $unactivatedSince->format('r') . "\n";

        // Pull back a list of accounts...
        $iterator = $this->getUserRepository()->getAccountsUnactivatedOlderThan($unactivatedSince);

        //---

        $counter = 0;

        foreach ($iterator as $user) {
            // Delete each account...
            $this->userManagementService->delete($user->id(), 'unactivated');

            $counter++;
        }

        echo "{$counter} accounts deleted.\n";

        return $counter;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param NotifyClient $notifyClient
     */
    public function setNotifyClient(NotifyClient $notifyClient)
    {
        $this->notifyClient = $notifyClient;
    }

    /**
     * @param SnsClient $snsClient
     */
    public function setSnsClient(SnsClient $snsClient)
    {
        $this->snsClient = $snsClient;
    }

    /**
     * @param UserManagementService $userManagementService
     */
    public function setUserManagementService(UserManagementService $userManagementService)
    {
        $this->userManagementService = $userManagementService;
    }
}
