<?php
namespace Application\Model\DataAccess;

use DateTime;

interface AuthTokenInterface {

    /**
     * Returns the token.
     *
     * @return string
     */
    public function id() : ?string;

    /**
     * Returns the owner of the token's user details.
     *
     * @return string
     */
    public function user() : ?string;

    /**
     * Date the token will current expire.
     *
     * @return DateTime
     */
    public function expiresAt() : ?DateTime;

    /**
     * Date the token was last updated (extended).
     *
     * @return DateTime
     */
    public function updatedAt() : ?DateTime;

    /**
     * Date the token was created.
     *
     * @return DateTime
     */
    public function createdAt() : ?DateTime;

}
