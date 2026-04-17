<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\PeopleToNotify;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\RequestInspectorTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Laminas\Diactoros\Response\HtmlResponse;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PeopleToNotifyConfirmDeleteHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly MvcUrlHelper $urlHelper,
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

        $templateParams = [
            'deleteRoute' => $this->urlHelper->generate(
                'lpa/people-to-notify/delete',
                ['lpa-id' => $lpa->id, 'idx' => $personIdx]
            ),
            'personName' => $notifiedPerson->name,
            'personAddress' => $notifiedPerson->address,
            'cancelUrl' => $this->urlHelper->generate(
                'lpa/people-to-notify',
                ['lpa-id' => $lpa->id]
            ),
        ];

        if ($isPopup) {
            $templateParams['isPopup'] = true;
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/people-to-notify/confirm-delete.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
