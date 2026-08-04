<?php

declare(strict_types=1);

namespace App\Handler\Testing;

use App\Service\ApiClient\Exception\ApiException;
use App\Service\Fixture\CypressFixtureService;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
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
        $data = json_decode((string) $request->getBody(), true) ?? [];

        try {
            return match ($request->getMethod()) {
                'POST'  => $this->create($data),
                'DELETE' => $this->delete($data),
                default => new EmptyResponse(405),
            };
        } catch (ApiException $ex) {
            return new JsonResponse(
                ['error' => $ex->getMessage()],
                $ex->getCode() >= 400 ? $ex->getCode() : 500,
            );
        }
    }

    private function create(array $data): ResponseInterface
    {
        $lpaCount = (int) ($data['lpaCount'] ?? 0);
        $lpaType  = (string) ($data['lpaType'] ?? 'property-and-financial');

        $result = $this->fixtureService->createUserWithLpas($lpaCount, $lpaType);

        return new JsonResponse($result, 201);
    }

    private function delete(array $data): ResponseInterface
    {
        $email    = (string) ($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $password === '') {
            return new EmptyResponse(400);
        }

        $this->fixtureService->deleteUser($email, $password);

        return new EmptyResponse(204);
    }
}
