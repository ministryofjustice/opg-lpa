<?php

declare(strict_types=1);

namespace App\Authentication\Adapter;

use App\Service\ApiClient\Client;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\ApiClient\Response\AuthResponse;
use App\Model\Service\Authentication\Identity\User;
use DateTime;
use Laminas\Authentication\Adapter\Exception\RuntimeException;
use Laminas\Authentication\Result;

/**
 * Mezzio port of Application\Model\Service\Authentication\Adapter\LpaAuthAdapter.
 */
class LpaAuthAdapter implements AdapterInterface
{
    private ?string $email = null;
    private ?string $password = null;

    public function __construct(private readonly Client $client)
    {
    }

    public function setEmail(#[\SensitiveParameter] $email): static
    {
        $this->email = trim(strtolower((string) $email));
        return $this;
    }

    public function setPassword(#[\SensitiveParameter] $password): static
    {
        $this->password = (string) $password;
        return $this;
    }

    public function authenticate(): Result
    {
        if ($this->email === null) {
            throw new RuntimeException('Email address not set');
        }

        if ($this->password === null) {
            throw new RuntimeException('Password not set');
        }

        $response    = new AuthResponse();
        $response->setErrorDescription('authentication-failed');
        $failureCode = Result::FAILURE_CREDENTIAL_INVALID;

        try {
            $result   = $this->client->httpPost('/v2/authenticate', [
                'username' => strtolower($this->email),
                'password' => $this->password,
            ]);
            $response = AuthResponse::buildFromResponse($result);
        } catch (ApiException $ex) {
            if ($ex->getCode() === 500) {
                $response->setErrorDescription('api-error');
                $failureCode = Result::FAILURE;
            } else {
                $msg = $ex->getMessage();
                if ($msg === 'account-locked/max-login-attempts') {
                    $response->setErrorDescription('locked');
                } elseif ($msg === 'account-not-active') {
                    $response->setErrorDescription('not-activated');
                }
            }
        }

        unset($this->password);
        $this->password = null;

        if (!$response->isAuthenticated()) {
            return new Result($failureCode, null, [$response->getErrorDescription()]);
        }

        $lastLogin = $response->getLastLogin();
        $lastLogin = $lastLogin ? new DateTime($lastLogin) : new DateTime();
        $identity  = new User(
            $response->getUserId(),
            $response->getToken(),
            $response->getExpiresIn(),
            $lastLogin,
        );

        $messages = [];
        if ($response->getInactivityFlagsCleared()) {
            $messages[] = 'inactivity-flags-cleared';
        }

        return new Result(Result::SUCCESS, $identity, $messages);
    }

    public function getSessionExpiry(#[\SensitiveParameter] string $token): array|null|string
    {
        try {
            return $this->client->httpGet('/v2/session-expiry', [], true, true, ['CheckedToken' => $token]);
        } catch (ApiException) {
            return null;
        }
    }

    public function setSessionExpiry(#[\SensitiveParameter] string $token, int $expireInSeconds): array|null|string
    {
        try {
            return $this->client->httpPost(
                '/v2/session-set-expiry',
                ['expireInSeconds' => $expireInSeconds],
                ['CheckedToken' => $token],
            );
        } catch (ApiException $ex) {
            return $ex->getMessage();
        }
    }
}
