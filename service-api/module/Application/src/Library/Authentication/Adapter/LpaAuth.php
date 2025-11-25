<?php

namespace Application\Library\Authentication\Adapter;

use Application\Library\Authentication\Identity;
use MakeShared\Logging\LoggerTrait;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Laminas\Authentication\Result;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Db\Exception\ExceptionInterface as LaminasExceptionInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Class LpaAuth
 * @package Application\Library\Authentication\Adapter
 */
class LpaAuth implements AdapterInterface, LoggerAwareInterface
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
     * psalm complains about catching an interface in this method; however, this is
     * because the Laminas ExceptionInterface does not implement Throwable
     * or extend Exception (i.e. issue is nothing to do with our code)
     *
     * @psalm-suppress InvalidCatch
     *
     * @return Result
     */
    public function authenticate(): Result
    {
        // Database errors during authentication are converted into a general 500 response from the API
        try {
            $data = $this->authenticationService->withToken($this->token, true);
        } catch (LaminasExceptionInterface $ex) {
            $this->getLogger()->error(
                'Unable to get user with token; possible database issue; message: ' . $ex->getMessage()
            );
            $this->getLogger()->error('Unable to get user with token; possible database issue', [
                'error_code' => 'EMAIL_UPDATE_USING_TOKEN_FAILED',
                'exception' => $ex,
            ]);
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
