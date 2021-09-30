<?php

namespace Application\Library\Authentication\Adapter;

use Application\Library\Authentication\Identity;
use Application\Logging\LoggerTrait;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Laminas\Authentication\Result;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Db\Exception\ExceptionInterface as LaminasExceptionInterface;

/**
 * Class LpaAuth
 * @package Application\Library\Authentication\Adapter
 */
class LpaAuth implements AdapterInterface
{
    use LoggerTrait;

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
        // Database errors during authentication are converted into a general 500 response from the API
        try {
            $data = $this->authenticationService->withToken($this->token, true);
        } catch (LaminasExceptionInterface $ex) {
            $this->getLogger()->err(
                'Unable to get user with token; possible database issue; message: ' . $ex->getMessage()
            );
            return new Result(Result::FAILURE, null);
        }

        $user = null;

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

        return new Result(is_null($user) ? Result::FAILURE_CREDENTIAL_INVALID : Result::SUCCESS, $user);
    }
}
