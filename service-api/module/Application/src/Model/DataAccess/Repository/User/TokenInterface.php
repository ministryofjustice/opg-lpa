<?php
namespace Application\Model\DataAccess\Repository\User;

use DateTime;

interface TokenInterface
{

    /**
     * Returns the token.
     *
     * @return string
     */
    public function id() : ?string;

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
