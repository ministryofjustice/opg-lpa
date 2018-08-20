<?php
namespace Application\Model\DataAccess\Repository\User;

use DateTime;
use Opg\Lpa\DataModel\User\User as UserModel;

interface UserRepositoryInterface
{

    /**
     * Returns a single user by username (email address).
     *
     * @param $username
     * @return UserInterface|null
     */
    public function getByUsername(string $username) : ?UserInterface;

    /**
     * @param $id
     * @return UserInterface|null
     */
    public function getById(string $id) : ?UserInterface;

    /**
     * @param $token
     * @return UserInterface|null
     */
    public function getByAuthToken(string $token) : ?UserInterface;

    /**
     * @param $token
     * @return UserInterface|null
     */
    public function getByResetToken(string $token) : ?UserInterface;

    /**
     * @param $id
     * @return bool
     */
    public function updateLastLoginTime(string $id) : bool;

    /**
     * Resets the user's failed login counter to zero.
     *
     * @param $id
     * @return bool
     */
    public function resetFailedLoginCounter(string $id) : bool;

    /**
     * Increments the user's failed login counter by 1.
     *
     * @param $id
     * @return bool
     */
    public function incrementFailedLoginCounter(string $id) : bool;

    /**
     * Creates a new user account
     *
     * @param $id
     * @param array $details
     * @return bool
     */
    public function create(string $id, array $details) : bool;

    /**
     * Delete the account for the passed user.
     *
     * NB: When an account is deleted, the document it kept, leaving only _id and a new deletedAt field.
     *
     * @param $id
     * @return bool|null
     */
    public function delete(string $id) : bool;

    /**
     * Activates a user account
     *
     * @param $token
     * @return bool|null
     */
    public function activate(string $token) : bool;

    /**
     * Updates a user's password.
     *
     * @param $userId
     * @param $passwordHash
     * @return bool
     */
    public function setNewPassword(string $userId, string $passwordHash) : bool;

    /**
     * Sets a new auth token.
     *
     * @param $userId
     * @param DateTime $expires
     * @param $token
     * @return bool
     */
    public function setAuthToken(string $userId, DateTime $expires, string $token) : bool;

    /**
     * Extends the authentication token.
     *
     * @param $userId
     * @param DateTime $expires
     * @return bool
     */
    public function extendAuthToken(string $userId, DateTime $expires) : bool;

    /**
     * @param $id
     * @param array $token
     * @return bool
     */
    public function addPasswordResetToken(string $id, array $token) : bool;

    /**
     * @param $token
     * @param $passwordHash
     * @return UpdatePasswordUsingTokenError
     */
    public function updatePasswordUsingToken(string $token, string $passwordHash) : ?UpdatePasswordUsingTokenError;

    /**
     * @param $id
     * @param array $token
     * @param $newEmail
     * @return bool
     */
    public function addEmailUpdateTokenAndNewEmail(string $id, array $token, string $newEmail) : bool;

    /**
     * @param $token
     * @return UpdateEmailUsingTokenResponse
     */
    public function updateEmailUsingToken(string $token) : UpdateEmailUsingTokenResponse;

    /**
     * Returns all accounts that have not been logged into since $since.
     *
     * If $withoutFlag is set, accounts that contain the passed flag will be excluded.
     *
     * @param DateTime $since
     * @param null $excludeFlag
     * @return iterable
     */
    public function getAccountsInactiveSince(DateTime $since, ?string $excludeFlag = null) : iterable;

    /**
     * Adds a new inactivity flag to an account.
     *
     * @param $userId
     * @param $flag
     * @return bool
     */
    public function setInactivityFlag(string $userId, string $flag) : bool;

    /**
     * Returns all accounts create before date $olderThan and that have not been activated.
     *
     * @param DateTime $olderThan
     * @return iterable
     */
    public function getAccountsUnactivatedOlderThan(DateTime $olderThan) : iterable;

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

    /**
     * Return a user's profile details
     *
     * @param $id
     * @return UserModel
     */
    public function getProfile($id) : ?UserModel;

    /**
     * Updates a user's profile. If it doesn't already exist, it's created.
     *
     * @param UserModel $data
     * @return bool
     */
    public function saveProfile(UserModel $data) : bool;
}
