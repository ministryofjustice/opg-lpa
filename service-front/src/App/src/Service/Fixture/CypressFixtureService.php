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
    private const string FIXTURE_PASSWORD = 'Password1234!';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ApiClient $apiClient,
    ) {
    }

    /**
     * @return array{email: string, password: string, userId: string, lpaIds: array<int, string>}
     */
    public function createUserWithLpas(int $lpaCount, string $lpaType, string $name = ''): array
    {
        $email    = $this->generateUniqueEmail();
        $userId = $this->createAndActivateUser($email);

        $this->authenticate($email);

        $this->setAboutYouDetails($userId, $email, $name);

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
            'password' => self::FIXTURE_PASSWORD,
            'userId'   => $userId,
            'lpaIds'   => $lpaIds,
        ];
    }

    public function deleteUser(string $email): void
    {
        $userId = $this->authenticate($email, allowFailure: true);

        try {
            $this->apiClient->httpDelete('/v2/user/' . $userId);
        } catch (ApiException $ex) {
            $this->logger->error('Cypress fixture cleanup: failed to delete user', [
                'email'      => $email,
                'statusCode' => $ex->getStatusCode(),
                'title'      => $ex->getTitle(),
                'body'       => $ex->getBody(),
                'exception'  => $ex,
            ]);
        }
    }

    public function createSharedSpace(string $sharedSpaceName, string $userEmail): ?string
    {
        try {
            $this->authenticate($userEmail);

            $result = $this->apiClient->httpPost(
                '/v2/shared-space/create',
                ['name' => $sharedSpaceName],
            );

            return $result['sharedSpaceId'] ?? null;
        } catch (ApiException $ex) {
            $this->logger->error('Cypress fixtures: failed to create shared space', [
                'sharedSpaceName' => $sharedSpaceName,
                'userEmail'       => $userEmail,
                'statusCode'      => $ex->getStatusCode(),
                'title'           => $ex->getTitle(),
                'body'            => $ex->getBody(),
                'exception'       => $ex,
            ]);

            return null;
        }
    }

    public function addMember(string $sharedSpaceId, string $userToAddId, string $userEmail, bool $isAdmin): void
    {
        try {
            $this->authenticate($userEmail);

            $this->apiClient->httpPost(
                '/v2/shared-space/members',
                ['sharedSpaceId' => $sharedSpaceId, 'userIdToAdd' => $userToAddId, 'isAdmin' => $isAdmin],
            );
        } catch (ApiException $ex) {
            $this->logger->error('Cypress fixtures: failed to add member to shared space', [
                'sharedSpaceId' => $sharedSpaceId,
                'userToAddId'   => $userToAddId,
                'userAddingEmail' => $userEmail,
                'isAdmin'       => $isAdmin,
                'statusCode'    => $ex->getStatusCode(),
                'title'         => $ex->getTitle(),
                'body'          => $ex->getBody(),
                'exception'     => $ex,
            ]);

            throw $ex;
        }
    }

    public function createInvite(string $sharedSpaceId, string $userEmail): mixed
    {
        try {
            $userId = $this->authenticate($userEmail);

            $response = $this->apiClient->httpPost(
                '/v2/shared-space/invite',
                [
                    'sharedSpaceId' => $sharedSpaceId,
                    'userId' => $userId,
                    'firstNames' => 'John',
                    'lastName' => 'Smith',
                    'email' => 'john.smith@example.com',
                    'isAdmin' => false,
                ],
            );
        } catch (ApiException $ex) {
            $this->logger->error('Cypress fixtures: failed to add member to shared space', [
                'sharedSpaceId' => $sharedSpaceId,
                'userEmail'     => $userEmail,
                'statusCode'    => $ex->getStatusCode(),
                'title'         => $ex->getTitle(),
                'body'          => $ex->getBody(),
                'exception'     => $ex,
            ]);

            throw $ex;
        }

        return ['accessCode' => $response['inviteCode']];
    }

    private function createAndActivateUser(string $email): string
    {
        $result = $this->assertArrayResult($this->apiClient->httpPost('/v2/users', [
            'username' => $email,
            'password' => self::FIXTURE_PASSWORD,
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
     * @return string|null
     */
    private function authenticate(string $email, bool $allowFailure = false): ?string
    {
        try {
            $credentials = $this->assertArrayResult($this->apiClient->httpPost('/v2/authenticate', [
                'username' => $email,
                'password' => self::FIXTURE_PASSWORD,
            ], anonymous: true));
        } catch (ApiException $ex) {
            if ($allowFailure) {
                return null;
            }

            throw $ex;
        }

        $this->apiClient->updateToken($credentials['token']);
        return $credentials['userId'];
    }

    private function setAboutYouDetails(string $userId, string $email, string $name = ''): void
    {
        $now = (new DateTime())->format('c');

        [$first, $last] = $this->splitName($name);

        $this->apiClient->httpPut('/v2/user/' . $userId, [
            'id'        => $userId,
            'createdAt' => $now,
            'updatedAt' => $now,
            'name'      => ['title' => 'Dr', 'first' => $first, 'last' => $last],
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

    /**
     * Splits a fixture "name" (e.g. "Member 1") into first/last parts for
     * the API's name field. Falls back to the default fixture name when
     * no name is given.
     *
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return ['Fixture', 'User'];
        }

        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? ''];
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
}
