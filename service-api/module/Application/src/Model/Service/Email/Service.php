<?php

namespace Application\Model\Service\Email;

use Application\Model\DataAccess\Repository\User\UpdateEmailUsingTokenResponse;
use Application\Model\DataAccess\Repository\User\UserInterface as User;
use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Application\Model\Service\AbstractService;
use DateTime;

class Service extends AbstractService
{
    use UserRepositoryTrait;

    const TOKEN_TTL = 86400; // 24 hours

    //-------------

    /**
     * @param string $userId
     * @param string $newEmail
     * @return array|string
     * @throws \Random\RandomException
     */
    public function generateToken(#[\SensitiveParameter] string $userId, #[\SensitiveParameter] string $newEmail): array|string
    {

        $validator = new \Laminas\Validator\EmailAddress();

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

        $token = sprintf('0x%s', bin2hex(random_bytes(16)));

        // Use base62 for shorter tokens
        $token = gmp_strval($token, 62);

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
     * @param string $token
     * @return UpdateEmailUsingTokenResponse
     */
    public function updateEmailUsingToken(#[\SensitiveParameter] string $token)
    {
        return $this->getUserRepository()->updateEmailUsingToken($token);
    }
}
