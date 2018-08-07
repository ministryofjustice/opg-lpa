<?php
namespace Application\Model\DataAccess\Repository\Auth;

use DateTime;
use Generator;

interface UserRepositoryInterface
{

    /**
     * Returns a single user by username (email address).
     *
     * @param $username
     * @return UserInterface|null
     */
    public function getByUsername($username) : ?UserInterface;

    /**
     * @param $id
     * @return UserInterface|null
     */
    public function getById($id) : ?UserInterface;

    /**
     * @param $token
     * @return UserInterface|null
     */
    public function getByAuthToken($token) : ?UserInterface;

    /**
     * @param $token
     * @return UserInterface|null
     */
    public function getByResetToken($token) : ?UserInterface;

    /**
     * @param $id
     * @return bool
     */
    public function updateLastLoginTime($id) : bool;

    /**
     * Resets the user's failed login counter to zero.
     *
     * @param $id
     * @return bool
     */
    public function resetFailedLoginCounter($id) : bool;

    /**
     * Increments the user's failed login counter by 1.
     *
     * @param $id
     * @return bool
     */
    public function incrementFailedLoginCounter($id) : bool;

    /**
     * Creates a new user account
     *
     * @param $id
     * @param array $details
     * @return bool
     */
    public function create($id, array $details) : bool;

    /**
     * Delete the account for the passed user.
     *
     * NB: When an account is deleted, the document it kept, leaving only _id and a new deletedAt field.
     *
     * @param $id
     * @return bool|null
     */
    public function delete($id) : bool;

    /**
     * Activates a user account
     *
     * @param $token
     * @return bool|null
     */
    public function activate($token) : bool;

    /**
     * Updates a user's password.
     *
     * @param $userId
     * @param $passwordHash
     * @return bool
     */
    public function setNewPassword($userId, $passwordHash) : bool;

    /**
     * Sets a new auth token.
     *
     * @param $userId
     * @param DateTime $expires
     * @param $token
     * @return bool
     */
    public function setAuthToken($userId, DateTime $expires, $token) : bool;

    /**
     * Extends the authentication token.
     *
     * @param $userId
     * @param DateTime $expires
     * @return bool
     */
    public function extendAuthToken($userId, DateTime $expires) : bool;

    /**
     * @param $id
     * @param array $token
     * @return bool
     */
    public function addPasswordResetToken($id, array $token) : bool;

    /**
     * @param $token
     * @param $passwordHash
     * @return UpdatePasswordUsingTokenError
     */
    public function updatePasswordUsingToken($token, $passwordHash) : ?UpdatePasswordUsingTokenError;

    /**
     * @param $id
     * @param array $token
     * @param $newEmail
     * @return bool
     */
    public function addEmailUpdateTokenAndNewEmail($id, array $token, $newEmail) : bool;

    /**
     * @param $token
     * @return UpdateEmailUsingTokenResponse
     */
    public function updateEmailUsingToken($token) : UpdateEmailUsingTokenResponse;

    /**
     * Returns all accounts that have not been logged into since $since.
     *
     * If $withoutFlag is set, accounts that contain the passed flag will be excluded.
     *
     * @param DateTime $since
     * @param null $excludeFlag
     * @return Generator
     */
    public function getAccountsInactiveSince(DateTime $since, $excludeFlag = null) : Generator;

    /**
     * Adds a new inactivity flag to an account.
     *
     * @param $userId
     * @param $flag
     * @return bool
     */
    public function setInactivityFlag($userId, $flag) : bool;

    /**
     * Returns all accounts create before date $olderThan and that have not been activated.
     *
     * @param DateTime $olderThan
     * @return Generator
     */
    public function getAccountsUnactivatedOlderThan(DateTime $olderThan) : Generator;

    /**
     * Counts the number of account in the system.
     *
     * @return int Account count
     */
    public function countAccounts() : int;

    /**
     * Counts the number of ACTIVATED account in the system.
     *
     * @param DateTime|null $since only include accounts activated $since
     * @return int Account count
     */
    public function countActivatedAccounts(DateTime $since = null) : int;

    /**
     * Counts the number of accounts that have been deleted.
     *
     * @return int Account count
     */
    public function countDeletedAccounts() : int;
}
