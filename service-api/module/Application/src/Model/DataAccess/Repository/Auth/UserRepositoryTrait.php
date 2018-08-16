<?php
namespace Application\Model\DataAccess\Repository\Auth;

trait UserRepositoryTrait
{

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @param UserRepositoryInterface $repo
     */
    public function setUserRepository(UserRepositoryInterface $repo)
    {
        $this->userRepository = $repo;
    }

    /**
     * @return UserRepositoryInterface
     */
    private function getUserRepository() : UserRepositoryInterface
    {
        if (!($this->userRepository instanceof UserRepositoryInterface)) {
            throw new \RuntimeException("Instance of UserRepository not set");
        }

        return $this->userRepository;
    }

}
