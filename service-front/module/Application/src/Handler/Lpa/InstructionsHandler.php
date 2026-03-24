<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Form\Lpa\InstructionsAndPreferencesForm;
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
use RuntimeException;

class InstructionsHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly Metadata $metadata,
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

        /** @var InstructionsAndPreferencesForm $form */
        $form = $this->formElementManager->get(
            'Application\Form\Lpa\InstructionsAndPreferencesForm',
            ['lpa' => $lpa]
        );

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            // set data for validation
            $form->setData($postData);

            if ($form->isValid()) {
                /** @var array $data */
                $data = $form->getData();
                $lpaId = $lpa->id;

                // persist data if it has changed

                if (
                    is_null($lpa->document->instruction)
                    || $data['instruction'] != $lpa->document->instruction
                ) {
                    $setOk = $this->lpaApplicationService->setInstructions(
                        $lpa,
                        $data['instruction']
                    );

                    if (!$setOk) {
                        throw new RuntimeException(
                            'API client failed to set LPA instructions for id: ' . $lpaId
                        );
                    }
                }

                if (
                    is_null($lpa->document->preference)
                    || $data['preference'] != $lpa->document->preference
                ) {
                    $setOk = $this->lpaApplicationService->setPreferences(
                        $lpa,
                        $data['preference']
                    );

                    if (!$setOk) {
                        throw new RuntimeException(
                            'API client failed to set LPA preferences for id: ' . $lpaId
                        );
                    }
                }

                if (
                    !isset($lpa->metadata)
                    || !isset($lpa->metadata['instruction-confirmed'])
                    || $lpa->metadata['instruction-confirmed'] !== true
                ) {
                    $this->metadata->setInstructionConfirmed($lpa);
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
            $form->bind($lpa->document->flatten());
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/instructions/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form' => $form,
                ]
            )
        );

        return new HtmlResponse($html);
    }
}
