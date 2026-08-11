<?php

declare(strict_types=1);

namespace App\Handler\Testing;

use App\Service\ApiClient\Exception\ApiException;
use App\Service\Fixture\CypressFixtureService;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function json_decode;

/**
 * Test-only endpoint used by Cypress to scaffold (POST) and tear down
 * (DELETE) a new user with N LPAs already attached. Only routed when
 * App\Feature::CypressFixtures is enabled - see routes.php.
 */
class CypressFixtureHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly CypressFixtureService $fixtureService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        $entity = $routeResult?->getMatchedParams()['entity'] ?? '';

        if ($entity === null) {
            return new JsonResponse(
                ['error' => 'Missing entity parameter'],
                400
            );
        }

        $data = json_decode((string) $request->getBody(), true) ?? [];
        $method = $request->getMethod();

        try {
            return match ([$method, $entity]) {
                ['POST', 'user']                => $this->createUser($data),
                ['DELETE', 'user']              => $this->deleteUser($data),
                ['POST', 'shared-space']        => $this->createSpace($data),
                ['POST', 'shared-space-member'] => $this->addMember($data),
                ['POST', 'shared-space-invite'] => $this->createInvite($data),
                default                         => new EmptyResponse(405),
            };
        } catch (ApiException $ex) {
            return new JsonResponse(
                ['error' => $ex->getMessage()],
                $ex->getCode() >= 400 ? $ex->getCode() : 500,
            );
        }
    }

    private function createUser(array $data): ResponseInterface
    {
        $lpaCount = (int) ($data['lpaCount'] ?? 0);
        $lpaType  = (string) ($data['lpaType'] ?? 'property-and-financial');
        $name     = (string) ($data['name'] ?? '');

        $result = $this->fixtureService->createUserWithLpas($lpaCount, $lpaType, $name);

        return new JsonResponse($result, 201);
    }

    private function deleteUser(array $data): ResponseInterface
    {
        $email    = (string) ($data['email'] ?? '');

        if ($email === '') {
            return new EmptyResponse(400);
        }

        $this->fixtureService->deleteUser($email);

        return new EmptyResponse(204);
    }

    private function createSpace(array $data): ResponseInterface
    {
        $sharedSpaceName = (string) ($data['sharedSpaceName'] ?? '');
        $userEmail = (string) ($data['userEmail'] ?? '');

        if ($sharedSpaceName === '' || $userEmail === '') {
            return new EmptyResponse(400);
        }

        $sharedSpaceId = $this->fixtureService->createSharedSpace($sharedSpaceName, $userEmail);

        if ($sharedSpaceId === null) {
            return new EmptyResponse(400);
        }

        return new JsonResponse(['sharedSpaceId' => $sharedSpaceId], 201);
    }

    private function addMember(array $data): ResponseInterface
    {
        $sharedSpaceId = (string) ($data['sharedSpaceId'] ?? '');
        $userToAddId = (string) ($data['userToAddId'] ?? '');
        $userAddingEmail = (string) ($data['userAddingEmail'] ?? '');
        $isAdmin = ($data['isAdmin'] ?? '');

        if ($sharedSpaceId === '' || $userToAddId === '' || $userAddingEmail === '' || $isAdmin === '') {
            return new EmptyResponse(400);
        }

        $this->fixtureService->addMember($sharedSpaceId, $userToAddId, $userAddingEmail, (bool) $isAdmin);

        return new EmptyResponse(201);
    }

    private function createInvite(array $data): ResponseInterface
    {
        $sharedSpaceId = (string) ($data['sharedSpaceId'] ?? '');
        $userEmail = (string) ($data['userEmail'] ?? '');

        if ($sharedSpaceId === '' || $userEmail === '') {
            return new EmptyResponse(400);
        }

        $result = $this->fixtureService->createInvite($sharedSpaceId, $userEmail);

        return new JsonResponse($result, 200);
    }
}
