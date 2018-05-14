<?php

namespace Application\Model\DataAccess;

use Application\Library\Authentication\Identity\User as UserIdentity;
use MongoDB\Collection;
use Opg\Lpa\DataModel\User\User;
use ZfcRbac\Service\AuthorizationServiceAwareInterface;
use ZfcRbac\Service\AuthorizationServiceAwareTrait;

/**
 * Class UserDal
 * @package Application\Model\DataAccess
 */
class UserDal implements AuthorizationServiceAwareInterface
{
    use AuthorizationServiceAwareTrait;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * UserDal constructor
     * @param Collection $collection
     */
    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Return a user with the correct email address
     *
     * @param $userId
     * @return User|null
     */
    public function findById($userId)
    {
        $user = $this->collection->findOne(['_id' => $userId ]);

        if (is_array($user)) {
            $user = ['id' => $userId] + $user;
            $user = new User($user);

            $this->injectEmailAddressFromIdentity($user);

            return $user;
        }

        return null;
    }

    /**
     * The authentication service is the authoritative email address provider
     *
     * @param User $user
     */
    public function injectEmailAddressFromIdentity(User $user)
    {
        $identity = $this->getAuthorizationService()->getIdentity();

        if ($identity instanceof UserIdentity) {
            $user->email = [
                'address' => $this->getAuthorizationService()->getIdentity()->email()
            ];
        }
    }
}
