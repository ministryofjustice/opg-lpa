<?php

declare(strict_types=1);

namespace App\Service\Fixture;

use App\Service\ApiClient\Client as ApiClient;
use App\Service\ApiClient\Exception\ApiException;
use DateTime;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function is_array;
use function microtime;
use function random_int;

/**
 * Gated behind App\Feature::CypressFixtures - see routes.php.
 *
 * Constructor-only dependencies (no ApiClientAwareInterface/setter) so this
 * can be autowired via Laminas\ServiceManager\AbstractFactory\
 * ReflectionBasedAbstractFactory - see dependencies.global.php.
 */
class CypressFixtureService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ApiClient $apiClient,
    ) {
    }

    /**
     * @return array{email: string, password: string, userId: string, lpaIds: array<int, string>}
     */
    public function createUserWithLpas(int $lpaCount, string $lpaType): array
    {
        $email    = $this->generateUniqueEmail();
        $password = $this->generatePassword();

        $userId = $this->createAndActivateUser($email, $password);

        ['token' => $token] = $this->authenticate($email, $password);
        $this->apiClient->updateToken($token);

        $this->setAboutYouDetails($userId, $email);

        $lpaIds = [];
        for ($i = 0; $i < $lpaCount; $i++) {
            $lpaIds[] = $this->createLpaWithDonor($userId, $lpaType);
        }

        $this->logger->info('Created Cypress fixture user', [
            'userId'   => $userId,
            'lpaCount' => $lpaCount,
        ]);

        return [
            'email'    => $email,
            'password' => $password,
            'userId'   => $userId,
            'lpaIds'   => $lpaIds,
        ];
    }

    public function deleteUser(string $email, string $password): void
    {
        $credentials = $this->authenticate($email, $password, allowFailure: true);

        if ($credentials === null) {
            $this->logger->warning('Cypress fixture cleanup: could not authenticate as user to delete', [
                'email' => $email,
            ]);

            return;
        }

        $this->apiClient->updateToken($credentials['token']);

        try {
            $this->apiClient->httpDelete('/v2/user/' . $credentials['userId']);
        } catch (ApiException $ex) {
            $this->logger->warning('Cypress fixture cleanup: failed to delete user', [
                'email'     => $email,
                'exception' => $ex,
            ]);
        }
    }

    private function createAndActivateUser(string $email, string $password): string
    {
        $result = $this->assertArrayResult($this->apiClient->httpPost('/v2/users', [
            'username' => $email,
            'password' => $password,
        ], anonymous: true));

        $userId          = (string) $result['userId'];
        $activationToken = (string) $result['activation_token'];

        $this->apiClient->httpPost('/v2/users', [
            'activationToken' => $activationToken,
        ], anonymous: true);

        return $userId;
    }

    /**
     * httpPost()/httpPut() are typed to return array|string|null depending on the response body, but
     * a 200/201 response from these endpoints is always a JSON object. Narrows the type for Psalm and
     * fails loudly if the API ever returns something unexpected, rather than silently returning null.
     *
     * @return array<string, mixed>
     */
    private function assertArrayResult(array|string|null $result): array
    {
        if (!is_array($result)) {
            throw new RuntimeException('Unexpected API response format: expected an array');
        }

        return $result;
    }

    /**
     * @return array{token: string, userId: string}|null
     */
    private function authenticate(string $email, string $password, bool $allowFailure = false): ?array
    {
        try {
            $result = $this->assertArrayResult($this->apiClient->httpPost('/v2/authenticate', [
                'username' => $email,
                'password' => $password,
            ], anonymous: true));
        } catch (ApiException $ex) {
            if ($allowFailure) {
                return null;
            }

            throw $ex;
        }

        return ['token' => (string) $result['token'], 'userId' => (string) $result['userId']];
    }

    private function setAboutYouDetails(string $userId, string $email): void
    {
        $now = (new DateTime())->format('c');

        $this->apiClient->httpPut('/v2/user/' . $userId, [
            'id'        => $userId,
            'createdAt' => $now,
            'updatedAt' => $now,
            'name'      => ['title' => 'Dr', 'first' => 'Fixture', 'last' => 'User'],
            'address'   => [
                'address1' => 'Bank End Farm House',
                'address2' => 'Undercliff Drive',
                'address3' => 'Ventnor, Isle of Wight',
                'postcode' => 'PO38 1UL',
            ],
            'dob'       => ['date' => '1988-10-22T00:00:00.000000+0000'],
            'email'     => ['address' => $email],
        ]);
    }

    private function createLpaWithDonor(string $userId, string $lpaType): string
    {
        $applicationsPath = '/v2/user/' . $userId . '/applications';

        $createResult = $this->assertArrayResult($this->apiClient->httpPost($applicationsPath));
        $lpaId        = (string) $createResult['id'];
        $lpaPath      = $applicationsPath . '/' . $lpaId;

        $this->apiClient->httpPut($lpaPath . '/type', ['type' => $lpaType]);

        $this->apiClient->httpPut($lpaPath . '/donor', [
            'name'       => ['title' => 'Mrs', 'first' => 'Nancy', 'last' => 'Garrison'],
            'otherNames' => '',
            'address'    => [
                'address1' => 'Bank End Farm House',
                'address2' => 'Undercliff Drive',
                'address3' => 'Ventnor, Isle of Wight',
                'postcode' => 'PO38 1UL',
            ],
            'dob'        => ['date' => '1988-10-22T00:00:00.000000+0000'],
            'email'      => ['address' => 'opglpademo+nancygarrison@example.org'],
            'canSign'    => false,
        ]);

        return $lpaId;
    }

    private function generateUniqueEmail(): string
    {
        $identifier = (string) (int) (microtime(true) * 1000.0) . (string) random_int(100000000, 999999999);

        return $identifier . '@example.org';
    }

    private function generatePassword(): string
    {
        return 'Fixture' . (string) (int) (microtime(true) * 1000.0) . (string) random_int(100000000, 999999999);
    }
}
