<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\PeopleToNotify;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PeopleToNotifyHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
        private readonly Metadata $metadata,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        $form = $this->formElementManager->get('Application\Form\Lpa\BlankMainFlowForm', [
            'lpa' => $lpa,
        ]);

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $this->metadata->setPeopleToNotifyConfirmed($lpa);

                $nextRoute = $flowChecker->nextRoute($currentRoute);

                return new RedirectResponse(
                    $this->urlHelper->generate(
                        $nextRoute,
                        ['lpa-id' => $lpa->id],
                        $flowChecker->getRouteOptions($nextRoute)
                    )
                );
            }
        }

        $peopleToNotifyParams = [];

        foreach ($lpa->document->peopleToNotify as $idx => $peopleToNotify) {
            $peopleToNotifyParams[] = [
                'notifiedPerson' => [
                    'name' => $peopleToNotify->name,
                    'address' => $peopleToNotify->address,
                ],
                'editRoute' => $this->urlHelper->generate(
                    'lpa/people-to-notify/edit',
                    ['lpa-id' => $lpa->id, 'idx' => $idx]
                ),
                'confirmDeleteRoute' => $this->urlHelper->generate(
                    'lpa/people-to-notify/confirm-delete',
                    ['lpa-id' => $lpa->id, 'idx' => $idx]
                ),
                'deleteRoute' => $this->urlHelper->generate(
                    'lpa/people-to-notify/delete',
                    ['lpa-id' => $lpa->id, 'idx' => $idx]
                ),
            ];
        }

        $templateParams = [
            'form' => $form,
            'peopleToNotify' => $peopleToNotifyParams,
        ];

        if (count($lpa->document->peopleToNotify) < 5) {
            $templateParams['addRoute'] = $this->urlHelper->generate(
                'lpa/people-to-notify/add',
                ['lpa-id' => $lpa->id]
            );
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/people-to-notify/index.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }
}
