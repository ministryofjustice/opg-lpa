<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\PeopleToNotify;

use Application\Form\Lpa\AbstractActorForm;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\PeopleToNotifyHandlerTrait;
use Application\Handler\Traits\RequestInspectorTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PeopleToNotifyEditHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;
    use PeopleToNotifyHandlerTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

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

        /** @var AbstractActorForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\PeopleToNotifyForm');
        $form->setAttribute(
            'action',
            $this->urlHelper->generate('lpa/people-to-notify/edit', ['lpa-id' => $lpa->id, 'idx' => $personIdx])
        );
        $form->setActorData('person to notify', $this->getActorsList($lpa, $personIdx));

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $notifiedPerson->populate($form->getModelDataFromValidatedForm());

                $setOk = $this->lpaApplicationService->setNotifiedPerson(
                    $lpa,
                    $notifiedPerson,
                    $notifiedPerson->id
                );

                if (!$setOk) {
                    throw new RuntimeException(
                        'API client failed to update notified person ' . $personIdx . ' for id: ' . $lpa->id
                    );
                }

                if ($isPopup) {
                    return new JsonResponse(['success' => true]);
                }

                $nextRoute = $flowChecker->nextRoute($currentRoute);

                return new RedirectResponse(
                    $this->urlHelper->generate(
                        $nextRoute,
                        ['lpa-id' => $lpa->id],
                        $flowChecker->getRouteOptions($nextRoute)
                    )
                );
            }
        } else {
            $form->bind($notifiedPerson->flatten());
        }

        $templateParams = [
            'form' => $form,
            'cancelUrl' => $this->urlHelper->generate(
                'lpa/people-to-notify',
                ['lpa-id' => $lpa->id]
            ),
        ];

        if ($isPopup) {
            $templateParams['isPopup'] = true;
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/people-to-notify/form.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
