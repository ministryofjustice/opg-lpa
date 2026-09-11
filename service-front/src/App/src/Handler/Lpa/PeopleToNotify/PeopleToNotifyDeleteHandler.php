<?php

declare(strict_types=1);

namespace App\Handler\Lpa\PeopleToNotify;

use App\Middleware\RequestAttribute;
use App\Service\ApiClient\Exception\ConflictException;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class PeopleToNotifyDeleteHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly UrlHelper $urlHelper,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $params = $routeResult instanceof RouteResult ? $routeResult->getMatchedParams() : [];
        $personIdx = $params['idx'] ?? null;

        if ($personIdx === null || !array_key_exists((int) $personIdx, $lpa->document->peopleToNotify)) {
            return new HtmlResponse('', 404);
        }

        $personIdx = (int) $personIdx;
        $personToNotifyId = $lpa->document->peopleToNotify[$personIdx]->id;

        // TODO(LPAL-2493): Get version from POST body instead
        $ifMatchVersion = $lpa->getVersion();
        try {
            if (!$this->lpaApplicationService->deleteNotifiedPerson($lpa, $personToNotifyId, $ifMatchVersion)) {
                throw new RuntimeException(
                    'API client failed to delete notified person ' . $personIdx . ' for id: ' . $lpa->id
                );
            }

            return new RedirectResponse(
                $this->urlHelper->generate('lpa/people-to-notify', ['lpa-id' => $lpa->id])
            );
        } catch (ConflictException $e) {
            $this->logger->info('Conflict deleting person to notify', ['exception' => $e]);
            throw $e;
        }
    }
}
