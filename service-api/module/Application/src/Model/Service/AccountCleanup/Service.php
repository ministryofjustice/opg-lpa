<?php

namespace Application\Model\Service\AccountCleanup;

use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollectionTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\UserManagement\Service as UserManagementService;
use Aws\Sns\SnsClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
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
    use UserRepositoryTrait;

    /**
     * @var array
     */
    private $config;

    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * @var SnsClient
     */
    private $snsClient;

    /**
     * @var UserManagementService
     */
    private $userManagementService;

    /**
     * Execute the account cleanup
     */
    public function cleanup()
    {
        $summary = array();

        $notificationCallback = $this->config['cleanup']['notification']['callback'];

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
        $iterator = $this->getUserRepository()->getAccountsInactiveSince($lastLoginBefore, $type);

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
                $this->getUserRepository()->setInactivityFlag($user->id(), $type);

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
     * @param GuzzleClient $guzzleClient
     */
    public function setGuzzleClient(GuzzleClient $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
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
