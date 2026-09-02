<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Form\Lpa\InstructionsAndPreferencesForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\Metadata;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class InstructionsAndPreferencesHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly Metadata $metadata,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        $form = $this->formElementManager->get(
            InstructionsAndPreferencesForm::class,
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
                $lpaId = $lpa->getId();

                // persist data if it has changed

                if (
                    (is_null($lpa->getDocument()->getInstruction()) || $data['instruction'] != $lpa->getDocument()->getInstruction())
                    || (is_null($lpa->getDocument()->getPreference()) || $data['preference'] != $lpa->getDocument()->getPreference())
                ) {
                    $setOk = $this->lpaApplicationService->setInstructionsPreferences(
                        $lpa,
                        $data['instruction'],
                        $data['preference']
                    );

                    if (!$setOk) {
                        throw new RuntimeException(
                            'API client failed to set LPA instructions and preferences for id: ' . $lpaId
                        );
                    }
                }

                $metadata = $lpa->getMetadata();

                if (
                    count($metadata) === 0
                    || !isset($metadata['instruction-confirmed'])
                    || $metadata['instruction-confirmed'] !== true
                ) {
                    $this->metadata->setInstructionConfirmed($lpa);
                }

                $nextRoute = $flowChecker->nextRoute($currentRoute);

                return new RedirectResponse(
                    $this->urlHelper->generate(
                        $nextRoute,
                        ['lpa-id' => $lpa->getId()],
                        $flowChecker->getRouteOptions($nextRoute)
                    )
                );
            }
        } else {
            $form->bind($lpa->getDocument()->flatten());
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
