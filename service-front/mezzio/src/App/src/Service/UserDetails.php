<?php

declare(strict_types=1);

namespace App\Service;

use App\Authentication\AuthenticationService;
use App\Service\ApiClient\ApiClientAwareInterface;
use App\Service\ApiClient\ApiClientTrait;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use App\Storage\MezzioSessionStorage;
use App\View\Twig\Traits\MoneyFormatterTrait;
use Exception;
use Laminas\Http\Response;
use MakeShared\DataModel\Lpa\Formatter;
use MakeShared\DataModel\User\User;
use MakeShared\Logging\LoggerTrait;
use Mezzio\Helper\UrlHelper;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;

class UserDetails implements ApiClientAwareInterface, LoggerAwareInterface
{
    use ApiClientTrait;
    use LoggerTrait;
    use MoneyFormatterTrait;

    // Email template identifiers (mirrored from AbstractEmailService)
    public const EMAIL_ACCOUNT_ACTIVATE                      = 'email-account-activate';
    public const EMAIL_FEEDBACK                              = 'email-feedback';
    public const EMAIL_LPA_REGISTRATION_WITH_PAYMENT1        = 'email-lpa-registration-with-payment1';
    public const EMAIL_LPA_REGISTRATION_WITH_CHEQUE_PAYMENT2 = 'email-lpa-registration-with-cheque-payment2';
    public const EMAIL_LPA_REGISTRATION_WITH_NO_PAYMENT3     = 'email-lpa-registration-with-no-payment3';
    public const EMAIL_NEW_EMAIL_ADDRESS_NOTIFY              = 'email-new-email-address-notify';
    public const EMAIL_NEW_EMAIL_ADDRESS_VERIFY              = 'email-new-email-address-verify';
    public const EMAIL_PASSWORD_CHANGED                      = 'email-password-changed';
    public const EMAIL_PASSWORD_RESET                        = 'email-password-reset';
    public const EMAIL_PASSWORD_RESET_NO_ACCOUNT             = 'email-password-reset-no-account';
    public const EMAIL_ACCOUNT_DUPLICATION_WARNING           = 'email-account-duplication-warning';

    private UrlHelper $urlHelper;
    private ?MezzioSessionStorage $sessionStorage = null;

    public function __construct(
        private readonly AuthenticationService $authenticationService,
        private readonly array $config,
        private readonly MailTransportInterface $mailTransport,
    ) {
    }

    // -------------------------------------------------------------------------
    // Infrastructure helpers
    // -------------------------------------------------------------------------

    public function getAuthenticationService(): AuthenticationService
    {
        return $this->authenticationService;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    protected function getUserId(): ?string
    {
        return $this->authenticationService->getIdentity()?->id();
    }

    public function getMailTransport(): MailTransportInterface
    {
        return $this->mailTransport;
    }

    public function setUrlHelper(UrlHelper $urlHelper): void
    {
        $this->urlHelper = $urlHelper;
    }

    public function setSessionStorage(MezzioSessionStorage $storage): void
    {
        $this->sessionStorage = $storage;
    }

    /**
     * @param null|string $name
     * @param (mixed|string)[] $params
     * @param true[] $options
     *
     * @psalm-param array{token?: mixed|string, id?: '1'} $params
     * @psalm-param array{force_canonical?: true} $options
     */
    public function url(string|null $name = null, array $params = [], array $options = []): string
    {
        $path = $this->urlHelper->generate((string) $name, $params);

        if (!empty($options['force_canonical'])) {
            $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            return $scheme . '://' . $host . $path;
        }

        return $path;
    }

    public function formatLpaId(int $lpaId): string
    {
        return Formatter::id($lpaId);
    }

    public function moneyFormat(mixed $money): string
    {
        return $this->formatMoney($money);
    }

    // -------------------------------------------------------------------------
    // Business logic (ported from Application\Model\Service\User\Details)
    // -------------------------------------------------------------------------

    public function getUserDetails(): bool|User
    {
        try {
            return new User($this->apiClient->httpGet('/v2/user/' . $this->getUserId()));
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to get user details from API', [
                'userId'    => $this->getUserId(),
                'exception' => $ex,
            ]);
        }

        return false;
    }

    /**
     * @throws RuntimeException
     */
    public function updateAllDetails(array $data): array|null|string
    {
        $this->getLogger()->info('Updating user details', [
            'userId' => $this->getUserId(),
        ]);

        $userDetails = $this->getUserDetails();
        $userDetails->populateWithFlatArray($data);

        if (array_key_exists('address', $data) && $data['address'] == null) {
            $userDetails->address = null;
        }

        if (!isset($data['dob-date'])) {
            $userDetails->dob = null;
        }

        $validator = $userDetails->validate();

        if ($validator->hasErrors()) {
            $this->getLogger()->warning('Unable to validate user details for update', [
                'userId'    => $this->getUserId(),
                'status'    => Response::STATUS_CODE_400,
                'exception' => $validator->getArrayCopy(),
            ]);

            throw new RuntimeException('Unable to save details');
        }

        try {
            return $this->apiClient->httpPut('/v2/user/' . $this->getUserId(), $userDetails->toArray());
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to update user details via API', [
                'userId'    => $this->getUserId(),
                'exception' => $ex,
            ]);

            throw $ex;
        }
    }

    public function requestEmailUpdate(
        #[\SensitiveParameter] string $email,
        #[\SensitiveParameter] string $currentAddress,
    ): bool|string {
        $identity = $this->authenticationService->getIdentity();

        $this->getLogger()->info('Requesting email update to new email', [
            'userId' => $this->getUserId(),
        ]);

        try {
            $this->apiClient->updateToken($identity->token());

            $result = $this->apiClient->httpPost(
                sprintf('/v2/users/%s/email', $this->getUserId()),
                ['newEmail' => strtolower($email)]
            );

            if (is_array($result) && isset($result['token'])) {
                $mailParameters = new MailParameters(
                    $currentAddress,
                    self::EMAIL_NEW_EMAIL_ADDRESS_NOTIFY,
                    ['newEmailAddress' => $email]
                );

                try {
                    $this->mailTransport->send($mailParameters);
                } catch (Exception $ex1) {
                    $this->getLogger()->warning('Failed to send new email address notification to old email address', [
                        'userId'    => $this->getUserId(),
                        'exception' => $ex1,
                    ]);
                }

                $changeEmailAddressUrl = $this->url(
                    'user/change-email-address/verify',
                    ['token' => $result['token']],
                    ['force_canonical' => true]
                );

                $mailParameters = new MailParameters(
                    $email,
                    self::EMAIL_NEW_EMAIL_ADDRESS_VERIFY,
                    ['changeEmailAddressUrl' => $changeEmailAddressUrl]
                );

                try {
                    $this->mailTransport->send($mailParameters);
                } catch (Exception $ex2) {
                    $this->getLogger()->error('Failed to send verify new email address email', [
                        'userId'    => $this->getUserId(),
                        'exception' => $ex2,
                    ]);

                    return 'failed-sending-email';
                }

                return true;
            }
        } catch (ApiException $ex3) {
            $this->getLogger()->error('Failed to request email update via API', [
                'userId'    => $this->getUserId(),
                'exception' => $ex3,
            ]);

            return match ($ex3->getMessage()) {
                'User already has this email'          => 'user-already-has-email',
                'Email already exists for another user' => 'email-already-exists',
                default                                => 'unknown-error',
            };
        }

        return 'unknown-error';
    }

    public function updateEmailUsingToken(#[\SensitiveParameter] string $emailUpdateToken): bool
    {
        $this->getLogger()->info('Update email using token');

        try {
            $this->apiClient->httpPost('/v2/users/email', [
                'emailUpdateToken' => $emailUpdateToken,
            ]);

            return true;
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to request email update using token', [
                'exception' => $ex,
            ]);
        }

        return false;
    }

    public function updatePassword(
        #[\SensitiveParameter] string $currentPassword,
        #[\SensitiveParameter] string $newPassword,
    ): bool|string {
        $identity = $this->authenticationService->getIdentity();

        $this->getLogger()->info('Updating password', [
            'userId' => $this->getUserId(),
        ]);

        try {
            $this->apiClient->updateToken($identity->token());

            $result = $this->apiClient->httpPost(
                sprintf('/v2/users/%s/password', $this->getUserId()),
                [
                    'currentPassword' => $currentPassword,
                    'newPassword'     => $newPassword,
                ]
            );

            if (is_array($result) && isset($result['token'])) {
                // Get email from live API instead of MVC session container
                $liveUser = $this->getUserDetails();
                $email = $liveUser instanceof User
                    ? (string) $liveUser->email->address
                    : '';

                if ($email !== '') {
                    $mailParameters = new MailParameters(
                        $email,
                        self::EMAIL_PASSWORD_CHANGED,
                        ['email' => $email]
                    );

                    try {
                        $this->mailTransport->send($mailParameters);
                    } catch (Exception $ex) {
                        $this->getLogger()->error('Send password changed email', [
                            'userId'    => $this->getUserId(),
                            'exception' => $ex,
                        ]);
                    }
                }

                // Update the in-memory identity token
                $identity->setToken($result['token']);

                // Persist the new token into the Mezzio session so the user
                // stays authenticated after the redirect
                if ($this->sessionStorage !== null) {
                    $this->sessionStorage->write($identity);
                }

                return true;
            }
        } catch (ApiException $ex) {
            $this->getLogger()->error('Password update request failed', [
                'userId'    => $this->getUserId(),
                'exception' => $ex,
            ]);
        }

        return 'unknown-error';
    }

    public function getTokenInfo(#[\SensitiveParameter] string $token): array
    {
        try {
            $response = $this->apiClient->httpPost('/v2/authenticate', [
                'authToken' => $token,
            ]);

            $success     = true;
            $expiresIn   = $response['expiresIn'] ?? null;
            $failureCode = null;
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to get token info', [
                'status'    => $ex->getStatusCode(),
                'exception' => $ex,
            ]);

            $success     = false;
            $expiresIn   = null;
            $failureCode = $ex->getStatusCode();
        }

        return [
            'success'     => $success,
            'failureCode' => $failureCode,
            'expiresIn'   => $expiresIn,
        ];
    }

    public function delete(): bool
    {
        $this->getLogger()->info('Deleting user and all their LPAs', [
            'userId' => $this->getUserId(),
        ]);

        try {
            $this->apiClient->httpDelete('/v2/user/' . $this->getUserId());
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to delete user', [
                'userId'    => $this->getUserId(),
                'exception' => $ex,
            ]);

            return false;
        }

        $this->getLogger()->info('User account deleted', [
            'userId' => $this->getUserId(),
        ]);

        return true;
    }

    public function requestPasswordResetEmail(#[\SensitiveParameter] string $email): bool|string
    {
        $this->getLogger()->info('User requested password reset email');

        try {
            $result = $this->apiClient->httpPost('/v2/users/password-reset', [
                'username' => strtolower($email),
            ]);

            if (is_array($result)) {
                if (isset($result['activation_token'])) {
                    return $this->sendAccountActivateEmail($email, $result['activation_token']);
                }

                if (isset($result['token'])) {
                    $forgotPasswordUrl = $this->url(
                        'forgot-password/callback',
                        ['token' => $result['token']],
                        ['force_canonical' => true]
                    );

                    $mailParameters = new MailParameters(
                        $email,
                        self::EMAIL_PASSWORD_RESET,
                        ['forgotPasswordUrl' => $forgotPasswordUrl]
                    );

                    try {
                        $this->mailTransport->send($mailParameters);
                    } catch (Exception $ex) {
                        $this->getLogger()->warning('Failed to send password reset email', [
                            'exception' => $ex,
                        ]);

                        return 'failed-sending-email';
                    }

                    return true;
                }
            }

            return 'unknown-error';
        } catch (ApiException $ex) {
            if ($ex->getCode() == 404) {
                $signUpUrl = $this->url('register', [], ['force_canonical' => true]);

                $mailParameters = new MailParameters(
                    $email,
                    self::EMAIL_PASSWORD_RESET_NO_ACCOUNT,
                    ['signUpUrl' => $signUpUrl]
                );

                try {
                    $this->mailTransport->send($mailParameters);
                } catch (Exception $ex) {
                    $this->getLogger()->error('Failed to send password reset email - no account', [
                        'exception' => $ex,
                    ]);

                    return 'failed-sending-email';
                }

                return true;
            }

            $this->getLogger()->error('Failed to request password reset email via API', [
                'exception' => $ex,
            ]);
        }

        return false;
    }

    private function sendAccountActivateEmail(
        #[\SensitiveParameter] string $email,
        #[\SensitiveParameter] string $activationToken,
    ): bool|string {
        $activateAccountUrl = $this->url(
            'register/confirm',
            ['token' => $activationToken],
            ['force_canonical' => true]
        );

        $mailParameters = new MailParameters(
            $email,
            self::EMAIL_ACCOUNT_ACTIVATE,
            ['activateAccountUrl' => $activateAccountUrl]
        );

        try {
            $this->mailTransport->send($mailParameters);
        } catch (Exception $ex) {
            $this->getLogger()->error('Failed to send account activation email', [
                'exception' => $ex,
            ]);

            return 'failed-sending-email';
        }

        return true;
    }

    public function setNewPassword(
        #[\SensitiveParameter] string $restToken,
        #[\SensitiveParameter] string $password,
    ): bool|string {
        $this->getLogger()->info('Setting new password following password reset');

        try {
            $result = $this->apiClient->httpPost('/v2/users/password', [
                'passwordToken' => $restToken,
                'newPassword'   => $password,
            ]);

            if (is_null($result)) {
                return true;
            }
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to set new password', [
                'exception' => $ex,
            ]);

            if ($ex->getMessage() === 'Invalid passwordToken') {
                return 'invalid-token';
            } elseif ($ex->getMessage() != null) {
                return trim($ex->getMessage());
            }
        }

        return 'unknown-error';
    }

    public function registerAccount(
        #[\SensitiveParameter] string $email,
        #[\SensitiveParameter] string $password,
    ): bool|string {
        $this->getLogger()->info('Account registration attempt');

        try {
            $result = $this->apiClient->httpPost('/v2/users', [
                'username' => strtolower($email),
                'password' => $password,
            ]);

            if (isset($result['activation_token'])) {
                $activateAccountUrl = $this->url(
                    'register/confirm',
                    ['token' => $result['activation_token']],
                    ['force_canonical' => true]
                );

                $mailParameters = new MailParameters(
                    $email,
                    self::EMAIL_ACCOUNT_ACTIVATE,
                    ['activateAccountUrl' => $activateAccountUrl]
                );

                try {
                    $this->mailTransport->send($mailParameters);
                } catch (Exception $ex1) {
                    $this->getLogger()->error('Failed to send account registration email', [
                        'exception' => $ex1,
                    ]);

                    return 'failed-sending-email';
                }

                return true;
            }
        } catch (ApiException $ex2) {
            if ($ex2->getMessage() == 'username-already-exists') {
                $mailParameters = new MailParameters(
                    $email,
                    self::EMAIL_ACCOUNT_DUPLICATION_WARNING
                );

                try {
                    $this->mailTransport->send($mailParameters);
                } catch (Exception $ex3) {
                    $this->getLogger()->warning('Failed sending warning email', [
                        'exception' => $ex3,
                    ]);

                    return 'failed-sending-warning-email';
                }

                return 'address-already-registered';
            }

            $this->getLogger()->error('Account registration failed', [
                'exception' => $ex2,
            ]);

            return 'api-error';
        }

        return 'unknown-error';
    }

    public function resendActivateEmail(#[\SensitiveParameter] string $email): bool|string
    {
        try {
            $result = $this->apiClient->httpPost('/v2/users/password-reset', [
                'username' => strtolower($email),
            ]);

            if (isset($result['activation_token'])) {
                return $this->sendAccountActivateEmail($email, $result['activation_token']);
            }
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to resend activation email', [
                'exception' => $ex,
            ]);
        }

        return false;
    }

    public function activateAccount(#[\SensitiveParameter] string $token): bool
    {
        try {
            $this->apiClient->httpPost('/v2/users', [
                'activationToken' => $token,
            ]);

            return true;
        } catch (ApiException $ex) {
            $this->getLogger()->error('Failed to activate account', [
                'exception' => $ex,
            ]);
        }

        return false;
    }
}
