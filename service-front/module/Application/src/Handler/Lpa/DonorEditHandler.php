<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\RequestInspectorTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class DonorEditHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
        private readonly ActorReuseDetailsService $actorReuseDetailsService,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var FormFlowChecker $flowChecker */
        $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

        $currentRoute = (string) $request->getAttribute(RequestAttribute::CURRENT_ROUTE_NAME);

        /** @var \Application\Form\Lpa\DonorForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\DonorForm');
        $form->setAttribute('action', $this->urlHelper->generate('lpa/donor/edit', ['lpa-id' => $lpa->id]));
        $form->setActorData('donor', $this->actorReuseDetailsService->getActorsList($lpa));

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $postData['canSign'] = (bool) ($postData['canSign'] ?? false);

            $form->setData($postData);

            if ($form->isValid()) {
                $donor = new Donor($form->getModelDataFromValidatedForm());

                if (!$this->lpaApplicationService->setDonor($lpa, $donor)) {
                    throw new RuntimeException(
                        'API client failed to update LPA donor for id: ' . $lpa->id
                    );
                }

                $this->updateCorrespondentData($lpa, $donor);

                if ($this->isXmlHttpRequest($request)) {
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
            $donor = $lpa->document->donor->flatten();
            $dob = $lpa->document->donor->dob->date;

            $donor['dob-date'] = [
                'day'   => $dob->format('d'),
                'month' => $dob->format('m'),
                'year'  => $dob->format('Y'),
            ];

            $form->bind($donor);
        }

        $cancelUrl = $this->urlHelper->generate('lpa/donor', ['lpa-id' => $lpa->id]);

        $templateParams = [
            'form' => $form,
            'cancelUrl' => $cancelUrl,
        ];

        if ($this->isXmlHttpRequest($request)) {
            $templateParams['isPopup'] = true;
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/donor/form.twig',
            array_merge(
                $this->getTemplateVariables($request),
                $templateParams
            )
        );

        return new HtmlResponse($html);
    }

    /**
     * If a correspondent is set as the donor, update the correspondent's name and address
     * to match the updated donor data.
     */
    private function updateCorrespondentData(Lpa $lpa, Donor $donor): void
    {
        $correspondent = $lpa->document->correspondent;

        if (
            $correspondent instanceof Correspondence
            && $correspondent->who === Correspondence::WHO_DONOR
        ) {
            if ($donor->name != $correspondent->name || $donor->address != $correspondent->address) {
                $correspondentData = $correspondent->toArray();
                unset($correspondentData['name']);
                $updatedCorrespondent = new Correspondence($correspondentData);
                $updatedCorrespondent->name = new LongName($donor->name->flatten());
                $updatedCorrespondent->address = $donor->address;

                if (!$this->lpaApplicationService->setCorrespondent($lpa, $updatedCorrespondent)) {
                    throw new RuntimeException(
                        'API client failed to update correspondent for id: ' . $lpa->id
                    );
                }
            }
        }
    }
}
