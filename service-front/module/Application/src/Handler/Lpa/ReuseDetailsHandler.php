<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\RequestInspectorTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class ReuseDetailsHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use RequestInspectorTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly MvcUrlHelper $urlHelper,
        private readonly ActorReuseDetailsService $actorReuseDetailsService,
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
        $callingUrl = $queryParams['calling-url'] ?? null;
        $includeTrusts = $queryParams['include-trusts'] ?? null;
        $actorName = $queryParams['actor-name'] ?? null;

        if (is_null($callingUrl) || is_null($includeTrusts) || is_null($actorName)) {
            throw new RuntimeException(
                'Required data missing when attempting to load the reuse details screen'
            );
        }

        $forCorrespondent = str_contains((string) $callingUrl, 'correspondent');

        if ($forCorrespondent) {
            $actorReuseDetails = $this->actorReuseDetailsService->getCorrespondentReuseDetails($user, $lpa);
        } else {
            $actorReuseDetails = $this->actorReuseDetailsService->getActorReuseDetails(
                $user,
                $lpa,
                (bool) $includeTrusts
            );
        }

        /** @var \Application\Form\Lpa\ReuseDetailsForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\ReuseDetailsForm', [
            'actorReuseDetails' => $actorReuseDetails,
        ]);

        $formAction = $this->urlHelper->generate(
            'lpa/reuse-details',
            ['lpa-id' => $lpa->id],
            ['query' => $queryParams]
        );
        $form->setAttribute('action', $formAction);

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

                // If the trust option was selected, adapt the return URL accordingly
                $returnUrl = $callingUrl . ($reuseDetailsIndex === 't' ? '-trust' : '');

                return new RedirectResponse(
                    $returnUrl . '?' . http_build_query([
                        'reuseDetailsIndex' => $reuseDetailsIndex,
                        'callingUrl'        => $callingUrl,
                    ])
                );
            }
        }

        $cancelUrl = substr((string) $callingUrl, 0, (int) strrpos((string) $callingUrl, '/'));

        $templateParams = [
            'form'      => $form,
            'cancelUrl' => $cancelUrl,
            'actorName' => $actorName,
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
