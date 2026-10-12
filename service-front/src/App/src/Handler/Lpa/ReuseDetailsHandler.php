<?php

declare(strict_types=1);

namespace App\Handler\Lpa;

use App\Form\Lpa\CorrespondentForm;
use App\Form\Lpa\ReuseDetailsForm;
use App\Handler\Traits\CommonTemplateVariablesTrait;
use App\Handler\Traits\RequestInspectorTrait;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\ApiClient\Exception\ConflictException;
use App\Service\CorrespondenceSetService;
use App\Service\Lpa\ActorReuseDetailsService;
use App\Service\SafeRedirectPath;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ReuseDetailsHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly UrlHelper $urlHelper,
        private readonly ActorReuseDetailsService $actorReuseDetailsService,
        private readonly CorrespondenceSetService $correspondenceSetService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Lpa $lpa */
        $lpa = $request->getAttribute(RequestAttribute::LPA);

        /** @var User $user */
        $user = $request->getAttribute(RequestAttribute::USER_DETAILS);

        $isPopup = $this->isXmlHttpRequest($request);

        $queryParams = $request->getQueryParams();

        $suppliedCallingUrl = $queryParams['calling-url'] ?? null;
        $callingUrl = SafeRedirectPath::filter($suppliedCallingUrl);

        if ($suppliedCallingUrl !== null && $callingUrl === null) {
            $this->logger->warning('lpa.reuse_details.calling_url_rejected', [
                'lpa_id' => $lpa->id,
            ]);

            throw new RuntimeException(
                'calling-url must be a path on this service when loading the reuse details screen'
            );
        }

        $includeTrusts = $queryParams['include-trusts'] ?? null;
        $actorName = $queryParams['actor-name'] ?? null;

        if (is_null($callingUrl) || is_null($includeTrusts) || is_null($actorName)) {
            throw new RuntimeException(
                'Required data missing when attempting to load the reuse details screen'
            );
        }

        $forCorrespondent = str_contains($callingUrl, 'correspondent');

        if ($forCorrespondent) {
            $actorReuseDetails = $this->actorReuseDetailsService->getCorrespondentReuseDetails($user, $lpa);
        } else {
            $actorReuseDetails = $this->actorReuseDetailsService->getActorReuseDetails(
                $user,
                $lpa,
                (bool) $includeTrusts
            );
        }

        /** @var ReuseDetailsForm $form */
        $form = $this->formElementManager->get(ReuseDetailsForm::class, [
            'actorReuseDetails' => $actorReuseDetails,
        ]);

        $formAction = $this->urlHelper->generate(
            'lpa/reuse-details',
            ['lpa-id' => $lpa->id],
            $queryParams
        );
        $form->setAttribute('action', $formAction);

        $conflictError = null;
        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                /** @var array $data */
                $data = $form->getData();
                $reuseDetailsIndex = $data['reuse-details'];

                try {
                    if ($forCorrespondent) {
                        if (array_key_exists($reuseDetailsIndex, $actorReuseDetails)) {
                            /** @var CorrespondentForm $form */
                            $correspondentForm = $this->formElementManager->get(CorrespondentForm::class);

                            $correspondentForm->bind($actorReuseDetails[$reuseDetailsIndex]['data']);

                            // If data is non-editable, process it directly
                            if (!$correspondentForm->isEditable()) {
                                $correspondentForm->isValid();
                                $correspondentData = $correspondentForm->getModelDataFromValidatedForm() ?? [];

                                /** @var FormFlowChecker $flowChecker */
                                $flowChecker = $request->getAttribute(RequestAttribute::FLOW_CHECKER);

                                $ifMatchVersion = (int)$postData['version'];
                                return $this->correspondenceSetService->setCorrespondent($lpa, $correspondentData, $flowChecker, $isPopup, $ifMatchVersion);
                            }
                        }
                    }

                    // If the trust option was selected, adapt the return URL accordingly
                    $returnUrl = $callingUrl . ($reuseDetailsIndex === 't' ? '-trust' : '');

                    return new RedirectResponse(
                        $returnUrl . '?' . http_build_query([
                            'reuseDetailsIndex' => $reuseDetailsIndex,
                            'callingUrl'        => $callingUrl,
                        ])
                    );
                } catch (ConflictException $e) {
                    $conflictError = $e;
                }
            }
        }

        $cancelUrl = substr($callingUrl, 0, (int) strrpos($callingUrl, '/'));

        $templateParams = [
            'form'      => $form,
            'cancelUrl' => $cancelUrl,
            'actorName' => $actorName,
            'conflictError' => $conflictError,
        ];

        if ($isPopup) {
            $templateParams['isPopup'] = true;
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/reuse-details/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                $templateParams
            )
        );

        return new HtmlResponse($html);
    }
}
