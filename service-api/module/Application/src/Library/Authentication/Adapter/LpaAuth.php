<?php

namespace Application\Library\Authentication\Adapter;

use Application\Library\Authentication\Identity;
use MakeLogger\Logging\LoggerTrait;
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

    /* @var AuthenticationService */
    private AuthenticationService $authenticationService;

    /* @var string */
    private string $token;

    /* @var array */
    private array $adminAccounts;

    /**
     * @param AuthenticationService $authenticationService
     * @param string $token
     * @param array $adminAccounts
     */
    public function __construct(AuthenticationService $authenticationService, string $token, array $adminAccounts)
    {
        $this->authenticationService = $authenticationService;
        $this->token = $token;
        $this->adminAccounts = $adminAccounts;
    }

    /**
     * @return Result
     */
    public function authenticate(): Result
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
