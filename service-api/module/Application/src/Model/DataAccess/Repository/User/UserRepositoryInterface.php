<?php

namespace Application\Model\DataAccess\Repository\User;

use DateTime;
use MakeShared\DataModel\User\User as ProfileUserModel;

interface UserRepositoryInterface
{
    /**
     * Returns a single user by username (email address).
     */
    public function getByUsername(string $username): ?UserInterface;

    /**
     * Returns zero or more users whose username (email address) approximately
     * matches the query (case insensitive, using LIKE).
     *
     * @param $query - string to match users against
     * @param $options - optional query criteria
     */
    public function matchUsers(string $query, array $options): iterable;

    public function getById(string $id): ?UserInterface;

    public function getByAuthToken(string $token): ?UserInterface;

    public function getByResetToken(string $token): ?UserInterface;

    public function updateLastLoginTime(string $id): void;

    /**
     * Resets the user's failed login counter to zero.
     */
    public function resetFailedLoginCounter(string $id): void;

    /**
     * Increments the user's failed login counter by 1.
     */
    public function incrementFailedLoginCounter(string $id): void;

    /**
     * Creates a new user account
     */
    public function create(string $id, array $details): bool;

    /**
     * Delete the account for the passed user.
     *
     * NB: When an account is deleted, the document it kept, leaving only _id and a new deletedAt field.
     */
    public function delete(string $id): bool;

    /**
     * Activates a user account
     */
    public function activate(string $token): bool|null;

    /**
     * Updates a user's password.
     */
    public function setNewPassword(string $userId, string $passwordHash): void;

    /**
     * Sets a new auth token.
     */
    public function setAuthToken(string $userId, DateTime $expires, string $token): bool;

    /**
     * Updates the authentication token expiry datetime.
     */
    public function updateAuthTokenExpiry(string $userId, DateTime $expires): bool;

    public function addPasswordResetToken(string $id, array $token): void;

    public function updatePasswordUsingToken(string $token, string $passwordHash): ?UpdatePasswordUsingTokenError;

    public function addEmailUpdateTokenAndNewEmail(string $id, array $token, string $newEmail): void;

    public function updateEmailUsingToken(string $token): UpdateEmailUsingTokenResponse;

    /**
     * Returns all accounts that have not been logged into since $since.
     *
     * If $withoutFlag is set, accounts that contain the passed flag will be excluded.
     */
    public function getAccountsInactiveSince(DateTime $since, ?string $excludeFlag = null): iterable;

    /**
     * Adds a new inactivity flag to an account.
     */
    public function setInactivityFlag(string $userId, string $flag): void;

    /**
     * Returns all accounts create before date $olderThan and that have not been activated.
     */
    public function getAccountsUnactivatedOlderThan(DateTime $olderThan): iterable;

    /**
     * Counts the number of account in the system.
     *
     * @return int Account count
     */
    public function countAccounts(): int;

    /**
     * Counts the number of ACTIVATED account in the system.
     *
     * @param DateTime|null $since only include accounts activated $since
     * @return int Account count
     */
    public function countActivatedAccounts(?DateTime $since = null): int;

    /**
     * Counts the number of accounts that have been deleted.
     *
     * @return int Account count
     */
    public function countDeletedAccounts(): int;

    /**
     * Return a user's profile details
     */
    public function getProfile($id): ?ProfileUserModel;

    /**
     * Updates a user's profile. If it doesn't already exist, it's created.
     */
    public function saveProfile(ProfileUserModel $data): void;
}
