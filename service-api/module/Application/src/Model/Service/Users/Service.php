<?php

namespace Application\Model\Service\Users;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\DateTime;
use Application\Model\DataAccess\Repository\User\LogRepositoryTrait;
use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Applications\Service as ApplicationService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\PasswordValidatorTrait;
use Opg\Lpa\DataModel\User\User;
use Zend\Validator\EmailAddress as EmailAddressValidator;
use Zend\Math\BigInteger\BigInteger;

class Service extends AbstractService
{
    use LogRepositoryTrait;
    use UserRepositoryTrait;
    use PasswordValidatorTrait;

    /**
     * @var ApplicationService
     */
    private $applicationsService;

    /**
     * @param $id
     * @return ValidationApiProblem|DataModelEntity|array|null|object|User
     */
    public function fetch($id)
    {
        //  Try to get an existing user
        $user = $this->getUserRepository()->getProfile($id);

        //  If there is no user create one now and ensure that the email address is correct
        if (is_null($user)) {
            $user = $this->save($id);
        }

        if ($user instanceof User) {
            return new DataModelEntity($user);
        }

        return $user;
    }

    /**
     * @param $data
     * @param $id
     * @return ValidationApiProblem|DataModelEntity|array|null|object|User
     */
    public function update($data, $id)
    {
        $user = $this->save($id, $data);

        // If it's not a user, it's a different kind of response, so return it.
        if (!$user instanceof User) {
            return $user;
        }

        return new DataModelEntity($user);
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        // Delete all applications for the user.
        $this->applicationsService->deleteAll($id);

//TODO - Fold this code into this function??
        $this->deleteUM($id, 'user-initiated');

        return true;
    }

    /**
     * @param $id
     * @param array $data
     * @return ValidationApiProblem|array|null|object|User
     */
    private function save($id, array $data = [])
    {
        // Protect these values from the client setting them manually.
        unset($data['id'], $data['email'], $data['createdAt'], $data['updatedAt']);

        $user = $this->getUserRepository()->getProfile($id);

        $new = false;

        if (is_null($user)) {
            $user = [
                'id'        => $id,
                'createdAt' => new DateTime(),
                'updatedAt' => new DateTime(),
            ];

            $new = true;
        } else {
            $user = $user->toArray();
        }

        //  Keep email up to date with what's in the auth service
//TODO - Refactor this code up from below??
        $authUserData = $this->get($id);
        $data['email']['address'] = $authUserData['username'];

        $data = array_merge($user, $data);

        $user = new User($data);

        if (!$new) {
            // We don't validate if it's new
            $validation = $user->validate();

            if ($validation->hasErrors()) {
                return new ValidationApiProblem($validation);
            }
        }

        $this->getUserRepository()->saveProfile($user);

        return $user;
    }





//TODO - NEED TO FOLD INTO THE CODE ABOVE???
    /**
     * @param $userId
     * @return array|string
     */
    public function get($userId)
    {
        $user = $this->getUserRepository()->getById($userId);

        if (is_null($user)) {
            return 'user-not-found';
        }

        return $user->toArray();
    }

    /**
     * @param string $username
     * @return array|bool
     */
    public function getByUsername(string $username)
    {
        $user = $this->getUserRepository()->getByUsername($username);

        if (is_null($user)) {
            //Check if user has been deleted
            $identityHash = $this->hashIdentity($username);
            $deletionLog = $this->getLogRepository()->getLogByIdentityHash($identityHash);

            if (is_null($deletionLog)) {
                return false;
            }

            return [
                'isDeleted' => true,
                'deletedAt' => $deletionLog['loggedAt']->toDateTime(),
                'reason' => $deletionLog['reason']
            ];
        }

        return $user->toArray();
    }

    /**
     * @param $username
     * @param $password
     * @return array|string
     */
    public function create($username, $password)
    {
        $emailValidator = new EmailAddressValidator();

        if (!$emailValidator->isValid($username)) {
            return 'invalid-username';
        }

        //  Check the username isn't already used...
        $user = $this->getUserRepository()->getByUsername($username);

        if (!is_null($user)) {
            return 'username-already-exists';
        }

        if (!$this->isPasswordValid($password)) {
            return 'invalid-password';
        }

        //  Create the account
        //  We use a loop here to ensure we retry to create the account if there's
        //  a clash with the userId or activation_token (despite this being extremely unlikely).
        do {
            // Create a 32 character user id and activation token.

            $userId = bin2hex(openssl_random_pseudo_bytes(16));
            $activationToken = bin2hex(openssl_random_pseudo_bytes(16));

            // Use base62 for shorter tokens
            $activationToken = BigInteger::factory('bcmath')->baseConvert($activationToken, 16, 62);

            $created = (bool)$this->getUserRepository()->create($userId, [
                'identity' => $username,
                'active' => false,
                'activation_token' => $activationToken,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'created' => new \DateTime(),
                'last_updated' => new \DateTime(),
                'failed_login_attempts' => 0,
            ]);
        } while (!$created);

        return [
            'userId' => $userId,
            'activation_token' => $activationToken,
        ];
    }

    /**
     * @param $token
     * @return bool|string
     */
    public function activate($token)
    {
        $result = $this->getUserRepository()->activate($token);

        if (is_null($result) || $result === false) {
            return 'account-not-found';
        }

        return true;
    }

    /**
     * @param $userId
     * @param $reason
     * @return bool|string
     */
    public function deleteUM($userId, $reason)
    {
        $user = $this->getUserRepository()->getById($userId);

        if (is_null($user)) {
            return 'user-not-found';
        }

        $result = $this->getUserRepository()->delete($userId);

        if ($result !== true) {
            return 'user-not-found';
        }

        // Record the account deletion in the log
        $details = [
            'identity_hash' => $this->hashIdentity($user->username()),
            'type' => 'account-deleted',
            'reason' => $reason,
            'loggedAt' => new \DateTime
        ];

        $this->getLogRepository()->addLog($details);

        return true;
    }

    /**
     * Hashes the passed identity, ensuring it's trimmed and lowercase.
     *
     * @param $identity
     * @return string
     */
    private function hashIdentity($identity)
    {
        return hash('sha512', strtolower(trim($identity)));
    }










    /**
     * @param ApplicationService $applicationsService
     */
    public function setApplicationsService(ApplicationService $applicationsService)
    {
        $this->applicationsService = $applicationsService;
    }
}
