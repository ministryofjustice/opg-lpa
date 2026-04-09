<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
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

class ReplacementAttorneyIndexHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
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

        /** @var \Application\Form\Lpa\BlankMainFlowForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $lpa]);

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                $this->metadata->setReplacementAttorneysConfirmed($lpa);

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

        $attorneysParams = [];

        foreach ($lpa->document->replacementAttorneys as $idx => $attorney) {
            $params = [
                'attorney' => [
                    'address' => $attorney->address,
                    'name'    => $attorney->name,
                ],
                'editRoute'          => $this->urlHelper->generate(
                    'lpa/replacement-attorney/edit',
                    ['lpa-id' => $lpa->id, 'idx' => $idx]
                ),
                'confirmDeleteRoute' => $this->urlHelper->generate(
                    'lpa/replacement-attorney/confirm-delete',
                    ['lpa-id' => $lpa->id, 'idx' => $idx]
                ),
                'deleteRoute'        => $this->urlHelper->generate(
                    'lpa/replacement-attorney/delete',
                    ['lpa-id' => $lpa->id, 'idx' => $idx]
                ),
            ];

            $attorneysParams[] = $params;
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/replacement-attorney/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'addRoute'  => $this->urlHelper->generate(
                        'lpa/replacement-attorney/add',
                        ['lpa-id' => $lpa->id]
                    ),
                    'lpaId'     => $lpa->id,
                    'attorneys' => $attorneysParams,
                    'form'      => $form,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
