<?php
namespace Application\Model\DataAccess\Repository\User;

use DateTime;

interface UserInterface
{

    /**
     * Returns an array representation of the user's basic info.
     *
     * @return array
     */
    public function toArray() : array;

    //---------------------------------------

    /**
     * Returns the user's id.
     *
     * @return string
     */
    public function id() : ?string;

    /**
     * Returns the user's username (email address).
     *
     * @return string
     */
    public function username() : ?string;

    /**
     * Has the user's account been activated.
     *
     * @return bool
     */
    public function isActive() : bool;

    /**
     * The user's hashed password
     *
     * @return string
     */
    public function password() : ?string;

    /**
     * The date the user's account was created.
     *
     * @return DateTime
     */
    public function createdAt() : ?DateTime;

    /**
     * The date the user's account was last updated.
     *
     * @return DateTime
     */
    public function updatedAt() : ?DateTime;

    /**
     * The date the user's account is set to be deleted.
     *
     * @return DateTime
     */
    public function deleteAt() : ?DateTime;

    /**
     * The date the user's account was last successfully logged into.
     *
     * @return DateTime
     */
    public function lastLoginAt() : ?DateTime;

    /**
     * The date the user's account was activated.
     *
     * @return DateTime
     */
    public function activatedAt() : ?DateTime;

    /**
     * The date the user's account was last unsuccessfully tied to be logged in to.
     *
     * @return DateTime
     */
    public function lastFailedLoginAttemptAt() : ?DateTime;

    /**
     * The number of consecutive login attempts the user's account has received.
     *
     * @return int
     */
    public function failedLoginAttempts() : int;

    /**
     * The user's activation token.
     *
     * @return string
     */
    public function activationToken() : ?string;

    /**
     * Returns the user's current authentication token (if present).
     *
     * @return TokenInterface|null
     */
    public function authToken() : ?TokenInterface;

    /**
     * Any account inactivity flags that may be set
     *
     * @return array|null
     */
    public function inactivityFlags() : ?array;

    /**
     * Sets the failed login attempts to zero in this instance.
     * NOTE - this does not change the value in the database!
     */
    public function resetFailedLoginAttempts();
}
