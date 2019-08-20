<?php

namespace Application\Library\Authentication\Adapter;

use Application\Library\Authentication\Identity;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Zend\Authentication\Result;
use Zend\Authentication\Adapter\AdapterInterface;

/**
 * Class LpaAuth
 * @package Application\Library\Authentication\Adapter
 */
class LpaAuth implements AdapterInterface
{
    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    /**
     * @var string
     */
    private $token;

    /**
     * @var array
     */
    private $adminAccounts;

    /**
     * @param AuthenticationService $authenticationService
     * @param $token
     * @param array $adminAccounts
     */
    public function __construct(AuthenticationService $authenticationService, $token, array $adminAccounts)
    {
        $this->authenticationService = $authenticationService;
        $this->token = $token;
        $this->adminAccounts = $adminAccounts;
    }

    /**
     * @return Result
     */
    public function authenticate()
    {
        $user = null;

        $data = $this->authenticationService->withToken($this->token, true);

        //  Clear up the token
        unset($this->token);

        if (isset($data['userId']) && isset($data['username'])) {
            $userId = $data['userId'];
            $username = $data['username'];

            $user = new Identity\User($userId, $username);

            if (in_array($username, $this->adminAccounts)) {
                $user->setAsAdmin();
            }
        }

        return new Result(is_null($user) ? Result::FAILURE : Result::SUCCESS, $user);
    }
}
