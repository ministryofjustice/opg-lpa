<?php

namespace Application\Controller\Version2\Auth;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json;
use Application\Library\Http\Response\NoContent;
use Application\Model\Service\Authentication\Service as AuthenticationService;
use Application\Model\Service\Password\Service;
use DateTime;
use Laminas\Mvc\Controller\AbstractRestfulController;
use MakeShared\Logging\MetricsLogger;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use SensitiveParameter;

class PasswordController extends AbstractRestfulController
{
    public function __construct(
        private readonly Service $service,
        private readonly AuthenticationService $authenticationService,
        private readonly MetricsLogger $metrics,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Change the user password either by providing the existing password or a password token
     */
    public function changeAction(): Json|ApiProblem
    {
        $userId = $this->params()->fromRoute('userId');

        $newPassword = $this->getBodyContent('newPassword');

        if (empty($newPassword)) {
            return new ApiProblem(400, 'Missing New Password');
        }

        if (!empty($userId)) {
            $currentPassword = $this->getBodyContent('currentPassword');

            if (empty($currentPassword)) {
                return new ApiProblem(400, 'Missing Current Password');
            }

            return $this->changeWithPassword($userId, $currentPassword, $newPassword);
        }

        //  Change the password using a token value
        $passwordToken = $this->getBodyContent('passwordToken');

        if (empty($passwordToken)) {
            return new ApiProblem(400, 'token required');
        }

        return $this->changeWithToken($passwordToken, $newPassword);
    }

    /**
     * Change the user password by providing the existing password
     * NOTE: This also re-executes the login so that the calling function has access to a new authentication token
     */
    private function changeWithPassword(#[SensitiveParameter] string $userId, #[SensitiveParameter] string $currentPassword, #[SensitiveParameter] string $newPassword): Json|ApiProblem
    {
        if (!$this->authenticationService->authenticateUserToken($this->getRequest(), $userId)) {
            return new ApiProblem(401, 'invalid-token');
        }

        $result = $this->service->changePassword($userId, $currentPassword, $newPassword);

        if (is_string($result)) {
            return new ApiProblem(401, $result);
        }

        $this->logger->info('User successfully changed their password');

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ($v instanceof DateTime ? $v->format('Y-m-d\TH:i:sO') : $v);
        }, $result);

        return new Json($result);
    }

    /**
     * Change the user password by providing password token
     */
    private function changeWithToken(#[SensitiveParameter] string $passwordToken, #[SensitiveParameter] string $newPassword): Json|ApiProblem
    {
        $result = $this->service->updatePasswordUsingToken($passwordToken, $newPassword);

        if ($result === 'invalid-token') {
            return new ApiProblem(400, 'Invalid passwordToken');
        }

        if ($result === 'invalid-password') {
            return new ApiProblem(400, 'Invalid password');
        }

        if (is_string($result)) {
            return new ApiProblem(400, "Unknown error: {$result}");
        }

        $this->metrics->userResetLegacyAccountPassword();

        return new NoContent();
    }

    /**
     * @throws RandomException
     */
    public function resetAction(): Json|ApiProblem
    {
        $username = $this->getBodyContent('username');
        if (empty($username)) {
            return new ApiProblem(400, 'username must be passed');
        }

        $result = $this->service->generateToken($username);

        if ($result === Service::ACCOUNT_USES_ONE_LOGIN) {
            $this->metrics->userRefusedPasswordReset();

            return new ApiProblem(403, 'Account uses GOV.UK One Login');
        }

        if ($result == 'user-not-found') {
            $this->logger->warning('Password reset request for unknown user', [
                'username' => $username
            ]);

            return new ApiProblem(404, 'User not found');
        }

        // Map DateTimes to strings
        $result = array_map(function ($v) {
            return ($v instanceof \DateTime) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        $this->logger->info('Password reset token requested', [
            'username' => $username
        ]);

        return new Json($result);
    }

    /**
     * Get data from the body content of the request
     *
     * @param null|string $varName
     *
     * @return mixed|null
     */
    private function getBodyContent(string|null $varName = null)
    {
        $data = $this->processBodyContent($this->getRequest());

        if (is_string($varName)) {
            // Try to get the specific variable from the data
            return (array_key_exists($varName, $data) ? $data[$varName] : null);
        }

        return $data;
    }
}
