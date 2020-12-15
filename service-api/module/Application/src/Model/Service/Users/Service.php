<?php

namespace Application\Model\Service\Users;

use ArrayObject;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\DateTime;
use Application\Model\DataAccess\Repository\User\LogRepositoryTrait;
use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Applications\Service as ApplicationService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\PasswordValidatorTrait;
use Opg\Lpa\DataModel\User\User;
use Laminas\Validator\EmailAddress as EmailAddressValidator;
use Laminas\Math\BigInteger\BigInteger;

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
            $userId = bin2hex(random_bytes(16));
            $activationToken = bin2hex(random_bytes(16));

            // Use base62 for shorter tokens
            $activationToken = BigInteger::factory('bcmath')->baseConvert($activationToken, 16, 62);

            $created = (bool)$this->getUserRepository()->create($userId, [
                'identity'              => $username,
                'active'                => false,
                'activation_token'      => $activationToken,
                'password_hash'         => password_hash($password, PASSWORD_DEFAULT),
                'created'               => new \DateTime(),
                'last_updated'          => new \DateTime(),
                'failed_login_attempts' => 0,
            ]);
        } while (!$created);

        return [
            'userId'            => $userId,
            'activation_token'  => $activationToken,
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
     * @param string $reason
     * @return bool
     */
    public function delete($id, $reason = 'user-initiated')
    {
        //  First delete all applications for the user
        $this->applicationsService->deleteAll($id);

        //  Get the user data before we attempt to delete it
        $user = $this->getUserRepository()->getById($id);

        if (!is_null($user)) {
            $result = $this->getUserRepository()->delete($id);

            if ($result === true) {
                //  Record the account deletion in the log
                $this->getLogRepository()->addLog([
                    'identity_hash' => $this->hashIdentity($user->username()),
                    'type'          => 'account-deleted',
                    'reason'        => $reason,
                    'loggedAt'      => new \DateTime(),
                ]);
            }
        }

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
        $authUser = $this->getUserRepository()->getById($id);
        $data['email']['address'] = $authUser->username();

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

    /**
     * @param string $username
     * @return array|bool
     */
    public function searchByUsername(string $username)
    {
        $user = $this->getUserRepository()->getByUsername($username);

        if (is_null($user)) {
            //  Check if user has been deleted
            $identityHash = $this->hashIdentity($username);
            $deletionLog = $this->getLogRepository()->getLogByIdentityHash($identityHash);

            if (is_null($deletionLog)) {
                return false;
            }

            return [
                'isDeleted' => true,
                'deletedAt' => $deletionLog['loggedAt'],
                'reason'    => $deletionLog['reason']
            ];
        }

        return $user->toArray();
    }

    /**
     * @param string $query to match against username
     * @param array $options See UserData.matchUsers()
     * @return array of arrays (each subarray derived from a UserModel instance)
     */
    public function matchUsers(string $query, array $options = [])
    {
        $users = new ArrayObject();

        $results = $this->getUserRepository()->matchUsers($query, $options);

        foreach ($results as $user) {
            $users->append($user->toArray());
        }

        return $users;
    }

    /**
     * @param ApplicationService $applicationsService
     */
    public function setApplicationsService(ApplicationService $applicationsService)
    {
        $this->applicationsService = $applicationsService;
    }
}
