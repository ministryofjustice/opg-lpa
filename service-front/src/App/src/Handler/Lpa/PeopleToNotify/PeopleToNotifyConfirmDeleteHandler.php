<?php

declare(strict_types=1);

namespace App\Handler\Lpa\PeopleToNotify;

use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\RequestInspectorTrait;
use App\Middleware\RequestAttribute;
use App\Service\ApiClient\Exception\ConflictException;
use App\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PeopleToNotifyConfirmDeleteHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;

    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly TemplateRendererInterface $renderer,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        $isPopup = $this->isXmlHttpRequest($request);

        /** @var RouteResult|null $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $params = $routeResult instanceof RouteResult ? $routeResult->getMatchedParams() : [];
        $personIdx = $params['idx'] ?? null;

        if ($personIdx === null || !array_key_exists((int) $personIdx, $lpa->document->peopleToNotify)) {
            return new HtmlResponse('', 404);
        }

        $personIdx = (int) $personIdx;
        $notifiedPerson = $lpa->document->peopleToNotify[$personIdx];

        $conflictError = null;
        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $ifMatchVersion = (int)$postData['version'];
            try {
                if (!$this->lpaApplicationService->deleteNotifiedPerson($lpa, $notifiedPerson->id, $ifMatchVersion)) {
                    throw new RuntimeException(
                        'API client failed to delete notified person ' . $personIdx . ' for id: ' . $lpa->id
                    );
                }

                if ($isPopup) {
                    return new JsonResponse(['success' => true]);
                }

                return new RedirectResponse(
                    $this->urlHelper->generate('lpa/people-to-notify', ['lpa-id' => $lpa->id])
                );
            } catch (ConflictException $e) {
                $conflictError = $e;
            }
        }

        $templateParams = [
            'isPopup' => $isPopup,
            'personName' => $notifiedPerson->name,
            'personAddress' => $notifiedPerson->address,
            'cancelUrl' => $this->urlHelper->generate(
                'lpa/people-to-notify',
                ['lpa-id' => $lpa->id]
            ),
            'actionUrl' => $this->urlHelper->generate(
                'lpa/people-to-notify/confirm-delete',
                ['lpa-id' => $lpa->id, 'idx' => $personIdx],
            ),
            'conflictError' => $conflictError,
        ];

        $html = $this->renderer->render(
            'application/authenticated/lpa/people-to-notify/confirm-delete.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
