<?php
namespace Auth\Model\Service\DataAccess;

use DateTime;

/**
 * Interface defining a single user.
 *
 * Interface UserInterface
 * @package Auth\Model\Service\DataAccess
 */
interface UserInterface {

    /**
     * Returns an array representation of the user's basic info.
     *
     * @return array
     */
    public function toArray();

    /**
     * Returns the user's id.
     *
     * @return string
     */
    public function id();

    /**
     * Returns the user's username (email address).
     *
     * @return string
     */
    public function username();

    /**
     * Has the user's account been activated.
     *
     * @return bool
     */
    public function isActive();

    /**
     * The user's hashed password
     *
     * @return string
     */
    public function password();

    /**
     * The date the user's account was created.
     *
     * @return DateTime
     */
    public function createdAt();

    /**
     * The date the user's account was last updated.
     *
     * @return DateTime
     */
    public function updatedAt();

    /**
     * The date the user's account is set to be deleted.
     *
     * @return DateTime
     */
    public function deleteAt();

    /**
     * The date the user's account was last successfully logged into.
     *
     * @return DateTime
     */
    public function lastLoginAt();

    /**
     * The date the user's account was activated.
     *
     * @return DateTime
     */
    public function activatedAt();

    /**
     * The date the user's account was last unsuccessfully tied to be logged in to.
     *
     * @return DateTime
     */
    public function lastFailedLoginAttemptAt();

    /**
     * The number of consecutive login attempts the user's account has received.
     *
     * @return int
     */
    public function failedLoginAttempts();

    /**
     * Returns the user's current authentication token (if present).
     *
     * @return TokenInterface|null
     */
    public function authToken();

    /**
     * The user's activation token.
     *
     * @return string
     */
    public function activationToken();

    /**
     * Any account inactivity flags that may be set
     *
     * @return array|null
     */
    public function inactivityFlags();

    /**
     * Sets the failed login attempts to zero in this instance.
     * NOTE - this does not change the value in the database!
     */
    public function resetFailedLoginAttempts();

}
