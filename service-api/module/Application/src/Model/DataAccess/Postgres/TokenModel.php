<?php
namespace Application\Model\DataAccess\Postgres;

use DateTime;
use Application\Model\DataAccess\Repository\User as UserRepository;

class TokenModel implements UserRepository\TokenInterface
{

    /**
     * Returns the token.
     *
     * @return string
     */
    public function id() : ?string
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Returns the owner of the token's user details.
     *
     * @return string
     */
    public function user() : ?string
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Date the token will current expire.
     *
     * @return DateTime
     */
    public function expiresAt() : ?DateTime
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Date the token was last updated (extended).
     *
     * @return DateTime
     */
    public function updatedAt() : ?DateTime
    {
        die(__METHOD__.' not implement');
    }

    /**
     * Date the token was created.
     *
     * @return DateTime
     */
    public function createdAt() : ?DateTime
    {
        die(__METHOD__.' not implement');
    }

}
