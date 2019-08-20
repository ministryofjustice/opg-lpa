<?php

namespace Application\Model\Service\Email;

use Application\Model\DataAccess\Repository\User\UserInterface as User;
use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Application\Model\Service\AbstractService;
use Zend\Math\BigInteger\BigInteger;
use DateTime;

class Service extends AbstractService
{
    use UserRepositoryTrait;

    const TOKEN_TTL = 86400; // 24 hours

    //-------------

    public function generateToken($userId, $newEmail)
    {

        $validator = new \Zend\Validator\EmailAddress();

        if (!$validator->isValid($newEmail)) {
            return 'invalid-email';
        }

        $user = $this->getUserRepository()->getById($userId);

        $userWithRequestedEmailAddress = $this->getUserRepository()->getByUsername($newEmail);

        if ($userWithRequestedEmailAddress instanceof User) {
            if ($userWithRequestedEmailAddress->id() == $user->id()) {
                return 'username-same-as-current';
            } else {
                return 'username-already-exists';
            }
        }

        if (!$user instanceof User) {
            return 'user-not-found';
        }

        $token = random_bytes(16);

        // Use base62 for shorter tokens
        $token = BigInteger::factory('bcmath')->baseConvert(bin2hex($token), 16, 62);

        $expires = new DateTime("+" . self::TOKEN_TTL . " seconds");

        $tokenDetails = [
            'token' => $token,
            'expiresIn' => self::TOKEN_TTL,
            'expiresAt' => $expires
        ];

        $this->getUserRepository()->addEmailUpdateTokenAndNewEmail($user->id(), $tokenDetails, $newEmail);

        return $tokenDetails;
    }

    /**
     * @param $token
     * @return \Application\Model\DataAccess\Repository\User\UpdateEmailUsingTokenResponse
     */
    public function updateEmailUsingToken($token)
    {
        return $this->getUserRepository()->updateEmailUsingToken($token);
    }
}
