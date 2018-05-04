<?php
namespace Application\Model\Service\DataAccess;

use DateTime;

interface TokenInterface {

    /**
     * Returns the token's id.
     *
     * @return string
     */
    public function id();

    /**
     * Returns the owner of the token's user id.
     *
     * @return mixed
     */
    public function user();

    /**
     * Date the token will current expire.
     *
     * @return DateTime
     */
    public function expiresAt();

    /**
     * Date the token was last updated (extended).
     *
     * @return DateTime
     */
    public function updatedAt();

    /**
     * Date the token was created.
     *
     * @return DateTime
     */
    public function createdAt();

}
