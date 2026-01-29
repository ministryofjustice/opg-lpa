<?php

namespace Application\Model\Service\Users;

use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use ArrayObject;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Repository\User\LogRepositoryTrait;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Application\Model\DataAccess\Repository\User\UserRepositoryTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Applications\Service as ApplicationService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\PasswordValidatorTrait;
use Application\Model\Service\TokenGenerationTrait;
use MakeShared\DataModel\User\User as ProfileUserModel;
use Laminas\Validator\EmailAddress as EmailAddressValidator;
use Random\RandomException;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LogRepositoryTrait;
    use PasswordValidatorTrait;
    use TokenGenerationTrait;
    use UserRepositoryTrait;

    /**
     * @var ApplicationService
     */
    private $applicationsService;

    /**
     * @param string $username
     * @param string $password
     * @return array|string
     * @throws RandomException
     */
    public function create(#[\SensitiveParameter] string $username, #[\SensitiveParameter] string $password): array|string
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

            $activationToken = $this->makeToken($username);

            $created = $this->getUserRepository()->create($userId, [
                'identity'              => $username,
                'active'                => false,
                'activation_token'      => $activationToken,
                'password_hash'         => password_hash($password, PASSWORD_DEFAULT),
                'created'               => new MillisecondDateTime(),
                'last_updated'          => new MillisecondDateTime(),
                'failed_login_attempts' => 0,
            ]);
        } while (!$created);

        return [
            'userId'            => $userId,
            'activation_token'  => $activationToken,
        ];
    }

    /**
     * @param string $token
     * @return bool|string
     */
    public function activate(#[\SensitiveParameter] string $token): bool|string
    {
        $result = $this->getUserRepository()->activate($token);

        if (is_null($result) || $result === false) {
            return 'account-not-found';
        }

        return true;
    }

    /**
     * @param $id
     * @return DataModelEntity
     */
    public function fetch($id)
    {
        // Get existing user
        $user = $this->getUserRepository()->getProfile($id);

        // If there is no user create one now and ensure that the email address is correct
        if (!$user instanceof ProfileUserModel) {
            $user = $this->save($id);
        } else {
            $lpaCount = $this->getApplicationRepository()->count(['user' => $user->getId()]);
            $user->setNumberOfLpas($lpaCount);
        }

        return new DataModelEntity($user);
    }

    /**
     * @param $data
     * @param $id
     * @return ValidationApiProblem|DataModelEntity|array|null|object|ProfileUserModel
     */
    public function update($data, $id)
    {
        $user = $this->save($id, $data);

        // If it's not a user, it's a different kind of response, so return it.
        if (!$user instanceof ProfileUserModel) {
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
                    'loggedAt'      => new MillisecondDateTime(),
                ]);
            }
        }

        return true;
    }

    /**
     * Hashes the passed identity, ensuring it's trimmed and lowercase.
     *
     * @param null|string $identity
     *
     * @return string
     */
    private function hashIdentity(string|null $identity)
    {
        return hash('sha512', strtolower(trim($identity)));
    }

    /**
     * @param $id
     * @param array $data
     * @return ValidationApiProblem|ProfileUserModel
     */
    private function save($id, array $data = [])
    {
        // Protect these values from the client setting them manually.
        unset($data['id'], $data['email'], $data['createdAt'], $data['updatedAt']);

        $user = $this->getUserRepository()->getProfile($id);

        $new = false;

        if (is_null($user)) {
            $user = [
                'id' => $id,
                'createdAt' => new MillisecondDateTime(),
                'updatedAt' => new MillisecondDateTime(),
            ];

            $new = true;
        } else {
            $user = $user->toArray();
        }

        // Keep email up to date with what's in the auth service (if anything)
        $authUser = $this->getUserRepository()->getById($id);
        if ($authUser instanceof UserInterface) {
            $data['email'] = [
                'address' => $authUser->username()
            ];
        }

        $data = array_merge($user, $data);

        $user = new ProfileUserModel($data);

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
    public function searchByUsername(#[\SensitiveParameter] string $username): bool|array
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
     * @return iterable Array of arrays; each subarray derived from a UserModel instance
     * @psalm-api
     */
    public function matchUsers(string $query, array $options = []): iterable
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
     * @psalm-api
     */
    public function setApplicationsService(ApplicationService $applicationsService): void
    {
        $this->applicationsService = $applicationsService;
    }
}
