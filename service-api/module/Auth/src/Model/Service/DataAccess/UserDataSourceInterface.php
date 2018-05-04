<?php
namespace Application\Model\Service\DataAccess;

interface UserDataSourceInterface {

    /**
     * @param $username
     * @return UserInterface
     */
    public function getByUsername( $username );

    /**
     * @param $username
     * @return UserInterface
     */
    public function getById( $id );

    public function getByAuthToken( $token );

    public function getByResetToken($token);

    public function updateLastLoginTime( $id );

    public function resetFailedLoginCounter( $id );

    public function incrementFailedLoginCounter( $id );

    public function create( $id, Array $details );

    /**
     * Delete the account for the passed user.
     *
     * NB: When an account is deleted, the document it kept, leaving only _id and a new deletedAt field.
     *
     * @param $id
     * @return bool|null
     */
    public function delete( $id );

    public function addPasswordResetToken( $id, Array $token );

    public function addEmailUpdateTokenAndNewEmail( $id, Array $token, $newEmail );

    /**
     * Updates a user's password.
     *
     * @param $userId
     * @param $passwordHash
     * @return bool
     */
    public function setNewPassword( $userId, $passwordHash );

    /**
     * Sets a new auth token.
     *
     * @param $userId
     * @param \DateTime $expires
     * @param $token
     * @return bool
     */
    public function setAuthToken( $userId, \DateTime $expires, $token );

    /**
     * Create or extend the authentication token.
     *
     * @param $userId
     * @param \DateTime $expires
     * @return bool
     */
    public function extendAuthToken( $userId, \DateTime $expires );

    /**
     * Delete the passed authentication token.
     *
     * @param $authToken
     * @return bool
     */
    public function removeAuthToken( $authToken );

    /**
     * Update user password, if token is valid
     *
     * @param string $token
     * @param string $newPassword
     */
    public function updatePasswordUsingToken( $token, $newPassword );

    /**
     * Update user email, if token is valid
     *
     * @param string $token
     */
    public function updateEmailUsingToken( $token );

    public function activate( $token );


    /**
     * Returns all accounts that have not been logged into since $since.
     *
     * If $withoutFlag is set, accounts that contain the passed flag will be excluded.
     *
     * @param \DateTime $since
     * @param null $excludeFlag
     * @return \Generator
     */
    public function getAccountsInactiveSince( \DateTime $since, $excludeFlag = null );


    /**
     * Adds a new inactivity flag to an account.
     *
     * @param $userId
     * @param $flag
     * @return bool
     */
    public function setInactivityFlag( $userId, $flag );

    /**
     * Returns all accounts create before date $olderThan and that have not been activated.
     *
     * @param \DateTime $olderThan
     * @return \Generator
     */
    public function getAccountsUnactivatedOlderThan( \DateTime $olderThan );


    /**
     * Counts the number of account in the system.
     *
     * @return int Account count
     */
    public function countAccounts();

    /**
     * Counts the number of ACTIVATED account in the system.
     *
     * @param \DateTime|null $since only include accounts activated $since
     * @return int Account count
     */
    public function countActivatedAccounts( \DateTime $since = null );

    /**
     * Counts the number of accounts that have been deleted.
     *
     * @return int Account count
     */
    public function countDeletedAccounts();

}
