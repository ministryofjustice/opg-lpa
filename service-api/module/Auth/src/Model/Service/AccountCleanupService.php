<?php

namespace Auth\Model\Service;

use Auth\Model\DataAccess\LogDataSourceInterface;
use Auth\Model\DataAccess\UserDataSourceInterface;
use Aws\Sns\SnsClient;
use DateTime;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use Opg\Lpa\Logger\LoggerTrait;

/**
 * - Deletes accounts after 9 months.
 * - Sends warning after 8 months and (9 months - 1 week).
 * - Delete accounts not activated after 24 hours.
 *
 * Warnings are sent by calling a HTTP based notification endpoint.
 * The auth service is not concerned with how that notification is then processed.
 *
 * Class AccountCleanupService
 * @package Auth\Model\Service
 */
class AccountCleanupService extends AbstractService
{
    use LoggerTrait;

    /**
     * @var UserManagementService
     */
    private $userManagementService;

    /**
     * @var SnsClient
     */
    private $snsClient;

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var array
     */
    private $config;

    public function __construct(
        UserDataSourceInterface $userDataSource,
        LogDataSourceInterface $logDataSource,
        UserManagementService $userManagementService,
        SnsClient $snsClient,
        GuzzleClient $guzzleClient,
        array $config
    ) {
        parent::__construct($userDataSource, $logDataSource);

        $this->userManagementService = $userManagementService;
        $this->snsClient = $snsClient;
        $this->guzzleClient = $guzzleClient;
        $this->config = $config;
    }

    public function cleanup($notificationCallback)
    {
        $summary = array();

        /**
         * 1 - Delete accounts >= -9 months
         * 2 - Notify accounts >= -9 months +1 week
         * 3 - Notify accounts >= -8 months
         */

        // Accounts inactive for 9 months...
        $summary['expired'] = $this->deleteExpiredAccounts(
            new DateTime('-9 months')
        );


        // Account inactive for 8 months and 3 weeks (1 week remaining)...
        $summary['1-week-notice'] = $this->sendWarningEmails(
            new DateTime('-9 months +1 week'),
            $notificationCallback,
            '1-week-notice'
        );


        // Account inactive for 8 months (1 month remaining)...
        $summary['1-month-notice'] = $this->sendWarningEmails(
            new DateTime('-8 months'),
            $notificationCallback,
            '1-month-notice'
        );

        //------------------------------------------

        // Remove accounts that have not been activated for > 24 hours.
        $summary['unactivated'] = $this->deleteUnactivatedAccounts(
            new DateTime('-24 hours')
        );

        //------------------------------------------

        $message = "Unactivated accounts deleted: {$summary['unactivated']}\n";
        $message .= "One month's notice emails sent: {$summary['1-month-notice']}\n";
        $message .= "One week's notice emails sent: {$summary['1-week-notice']}\n";
        $message .= "Expired accounts deleted: {$summary['expired']}\n";
        $message .= "\nLove,\n" . $this->config['stack']['name'];

        //---

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
     * @param DateTime $lastLoginBefore
     * @param $callback
     * @param $type
     * @return int The number of users notified
     */
    private function sendWarningEmails(DateTime $lastLoginBefore, $callback, $type)
    {

        echo "Sending {$type} warning notifications to accounts inactive since " . $lastLoginBefore->format('r') . "\n";

        //---

        // Pull back a list of accounts...
        $iterator = $this->getUserDataSource()->getAccountsInactiveSince($lastLoginBefore, $type);

        //---

        $counter = 0;

        foreach ($iterator as $user) {
            // Tell users the day before, giving them that full day to login.
            $notificationDate = $user->lastLoginAt()->add(\DateInterval::createFromDateString('+9 months'));

            try {
                // Call the notification callback...
                // This will thrown an exception on any errors.
                $this->guzzleClient->post($callback, [
                    'form_params' => [
                        'Type' => $type,
                        'Username' => $user->username(),
                        'Date' => $notificationDate->format('Y-m-d'),
                    ],
                    'headers' => [
                        'Token' => $this->config['cleanup']['notification']['token'],
                    ],
                ]);

                // Flag the account as 'notification sent'...
                $this->getUserDataSource()->setInactivityFlag($user->id(), $type);

                $counter++;
            } catch (GuzzleClientException $e) {
                echo "GuzzleClientException: " . $e->getMessage() . "\n";

                // Guzzle exceptions aren't too bad, we will just retry tomorrow.

                $this->getLogger()->warn(
                    'Unable to send account expiry notification',
                    ['exception' => $e->getMessage()]
                );
            } catch (Exception $e) {
                echo "Exception: " . $e->getMessage() . "\n";

                // Other types of exception are worse; things still might not work tomorrow.

                $this->getLogger()->alert(
                    'Unable to send account expiry notification',
                    ['exception' => $e->getMessage()]
                );
            }
        }

        echo "{$counter} notifications sent.\n";

        return $counter;
    }

    /**
     * Delete all accounts that have expired.
     *
     * @param DateTime $lastLoginBefore
     * @return int The number of accounts deleted
     */
    private function deleteExpiredAccounts(DateTime $lastLoginBefore)
    {

        echo "Deleting accounts inactive since " . $lastLoginBefore->format('r') . "\n";

        //---

        // Holds the delete account logic.
        $service = $this->userManagementService;

        //---

        // Pull back a list of accounts...
        $iterator = $this->getUserDataSource()->getAccountsInactiveSince($lastLoginBefore);

        //---

        $counter = 0;

        foreach ($iterator as $user) {
            // Delete each account...
            $service->delete($user->id(), 'expired');

            //  Delete the user account and LPA data in the api database via the API service
            try {
                $cleanUpConfig = $this->config['cleanup'];

                //  Create the delete target
                $deleteTarget = $cleanUpConfig['api-target'] . $user->id();

                $this->guzzleClient->delete($deleteTarget, [
                    'headers' => [
                        'AuthCleanUpToken' => $cleanUpConfig['api-token'],
                    ],
                ]);
            } catch (Exception $e) {
                //  Output and log the error so the issue can be investigated
                echo "Exception: " . $e->getMessage() . "\n";

                $this->getLogger()->alert('Unable to clean up expired account on the API service', [
                    'exception' => $e->getMessage()
                ]);
            }

            $counter++;
        }

        echo "{$counter} accounts deleted.\n";

        return $counter;
    }

    /**
     * Delete all accounts created before time x that have not yet been activated.
     *
     * @param DateTime $unactivatedSince
     * @return int The number of accounts deleted
     */
    private function deleteUnactivatedAccounts(DateTime $unactivatedSince)
    {

        echo "Deleting unactivated accounts created before " . $unactivatedSince->format('r') . "\n";

        //---

        // Holds the delete account logic.
        $service = $this->userManagementService;

        //---

        // Pull back a list of accounts...
        $iterator = $this->getUserDataSource()->getAccountsUnactivatedOlderThan($unactivatedSince);

        //---

        $counter = 0;

        foreach ($iterator as $user) {
            // Delete each account...
            $service->delete($user->id(), 'unactivated');

            $counter++;
        }

        echo "{$counter} accounts deleted.\n";

        return $counter;
    }
}
