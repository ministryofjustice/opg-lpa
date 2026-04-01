<?php

declare(strict_types=1);

namespace Application\Handler\Lpa\PrimaryAttorney;

use Application\Form\Lpa\AbstractActorForm;
use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Handler\Traits\PrimaryAttorneyHandlerTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Applicant as ApplicantService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Application\Model\Service\Session\SessionUtility;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class PrimaryAttorneyAddHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use PrimaryAttorneyHandlerTrait;

    public function __construct(
        private readonly TemplateRendererInterface $renderer,
        private readonly FormElementManager $formElementManager,
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly MvcUrlHelper $urlHelper,
        private readonly ApplicantService $applicantService,
        private readonly ReplacementAttorneyCleanup $replacementAttorneyCleanup,
        private readonly SessionUtility $sessionUtility,
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
        $isPost = strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST;

        $templateParams = [
            'isPopup' => $isPopup,
        ];

        /** @var User|null $userDetails */
        $userDetails = $request->getAttribute(RequestAttribute::USER_DETAILS);

        $queryParams = $request->getQueryParams();
        $reuseDetailsIndexFromQuery = $queryParams['reuseDetailsIndex'] ?? null;

        $actorReuseDetails = $this->getActorReuseDetails($lpa, $userDetails);

        if (!$isPost) {
            if ($reuseDetailsIndexFromQuery === null) {
                $reuseCount = count($actorReuseDetails);

                if ($reuseCount === 1) {
                    $templateParams['displayReuseSessionUserLink'] = true;
                } elseif ($reuseCount > 1) {
                    $reuseDetailsUrl = $this->urlHelper->generate('lpa/reuse-details', [
                        'lpa-id' => $lpa->id,
                    ], [
                        'query' => [
                            'calling-url' => $request->getUri()->getPath(),
                            'include-trusts' => true,
                            'actor-name' => 'Attorney',
                        ],
                    ]);

                    return new RedirectResponse($reuseDetailsUrl);
                }
            }
        }

        /** @var AbstractActorForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\AttorneyForm');
        $form->setAttribute(
            'action',
            $this->urlHelper->generate('lpa/primary-attorney/add', ['lpa-id' => $lpa->id])
        );
        $form->setActorData('attorney', $this->getActorsList($lpa));


        if (!$isPost && $reuseDetailsIndexFromQuery !== null) {
            $reuseDetails = $this->getActorReuseDetails($lpa, $userDetails);
            if (array_key_exists($reuseDetailsIndexFromQuery, $reuseDetails)) {
                $form->bind($reuseDetails[$reuseDetailsIndexFromQuery]['data']);
            }
        }

        if ($isPost) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $reuseDetailsIndex = $postData['reuse-details'] ?? null;
            $reuseHandled = false;

            if ($reuseDetailsIndex === '0' && count($actorReuseDetails) === 1) {
                $actorDetailsToReuse = array_pop($actorReuseDetails);
                $form->bind($actorDetailsToReuse['data']);
                $reuseHandled = true;
            }

            if (!$reuseHandled) {
                $form->setData($postData);

                if ($form->isValid()) {
                    $addOk = $this->lpaApplicationService->addPrimaryAttorney(
                        $lpa,
                        new Human($form->getModelDataFromValidatedForm())
                    );

                    if (!$addOk) {
                        throw new RuntimeException(
                            'API client failed to add a primary attorney for id: ' . $lpa->id
                        );
                    }

                    $this->replacementAttorneyCleanup->cleanUp($lpa);
                    $this->applicantService->cleanUp($lpa);

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
            }
        }

        if (count($this->getActorReuseDetails($lpa, $userDetails)) > 1) {
            $templateParams['backButtonUrl'] = str_replace(
                'add-trust',
                'add',
                $this->urlHelper->generate('lpa/primary-attorney/add', ['lpa-id' => $lpa->id])
            );
        }

        $templateParams['form'] = $form;

        if ($this->allowTrust($lpa)) {
            $templateParams['switchAttorneyTypeRoute'] = 'lpa/primary-attorney/add-trust';
        }

        // Cancel URL
        $templateParams['cancelUrl'] = $this->urlHelper->generate(
            'lpa/primary-attorney',
            ['lpa-id' => $lpa->id]
        );

        $html = $this->renderer->render(
            'application/authenticated/lpa/primary-attorney/person-form.twig',
            array_merge($this->getTemplateVariables($request), $templateParams)
        );

        return new HtmlResponse($html);
    }

    /**
     * Return an array of actor details available for reuse.
     *
     * For the primary attorney add screen this includes:
     * - The current session user (if their name hasn't already been used)
     * - Actors from the seed LPA (if this LPA is a clone)
     */
    private function getActorReuseDetails(Lpa $lpa, ?User $userDetails): array
    {
        $actorReuseDetails = [];

        // Add current user details if not already used
        if ($userDetails instanceof User && $userDetails->name !== null) {
            $alreadyUsed = false;

            foreach ($this->getAllActorsList($lpa) as $actor) {
                if (
                    strtolower((string) $userDetails->name->first) === strtolower((string) $actor['firstname'])
                    && strtolower((string) $userDetails->name->last) === strtolower((string) $actor['lastname'])
                ) {
                    $alreadyUsed = true;
                    break;
                }
            }

            if (!$alreadyUsed) {
                $userData = $userDetails->flatten();
                $userData['who'] = 'other';

                if (($dateOfBirth = $userDetails->dob) instanceof Dob) {
                    $userData['dob-date'] = [
                        'day'   => $dateOfBirth->date->format('d'),
                        'month' => $dateOfBirth->date->format('m'),
                        'year'  => $dateOfBirth->date->format('Y'),
                    ];
                }

                $actorReuseDetails[] = [
                    'label' => sprintf(
                        '%s %s (myself)',
                        $userDetails->name->first,
                        $userDetails->name->last
                    ),
                    'data' => $userData,
                ];
            }
        }

        // Add seed LPA actor details if this LPA is a clone
        $seedId = (string) $lpa->seed;

        if (!empty($seedId)) {
            $sessionSeedData = $this->sessionUtility->getFromMvc('clone', $seedId);
            $seedDetails = [];

            if ($sessionSeedData === null) {
                $seedDetails = $this->lpaApplicationService->getSeedDetails($lpa->id);
                $this->sessionUtility->setInMvc('clone', $seedId, $seedDetails);
            } elseif (is_array($sessionSeedData)) {
                $seedDetails = $sessionSeedData;
            }

            foreach ($seedDetails as $type => $actorData) {
                switch ($type) {
                    case 'donor':
                        $actorReuseDetails[] = $this->buildReuseEntry($actorData, '(was the donor)');
                        break;
                    case 'certificateProvider':
                        $actorReuseDetails[] = $this->buildReuseEntry($actorData, '(was the certificate provider)');
                        break;
                    case 'primaryAttorneys':
                    case 'replacementAttorneys':
                        $suffixText = $type === 'primaryAttorneys'
                            ? '(was a primary attorney)'
                            : '(was a replacement attorney)';
                        foreach ($actorData as $singleActorData) {
                            $isTrust = (($singleActorData['type'] ?? '') === 'trust');
                            if ($isTrust && !$this->allowTrust($lpa)) {
                                continue;
                            }
                            $entry = $this->buildReuseEntry($singleActorData, $suffixText);
                            if ($isTrust) {
                                $actorReuseDetails['t'] = $entry;
                            } else {
                                $actorReuseDetails[] = $entry;
                            }
                        }
                        break;
                    case 'peopleToNotify':
                        foreach ($actorData as $singleActorData) {
                            $actorReuseDetails[] = $this->buildReuseEntry(
                                $singleActorData,
                                '(was a person to be notified)'
                            );
                        }
                        break;
                    case 'correspondent':
                        $actorType = ($actorData['who'] ?? 'other');
                        if ($actorType === 'other') {
                            $actorReuseDetails[] = $this->buildReuseEntry(
                                $actorData,
                                '(was the correspondent)'
                            );
                        }
                        break;
                }
            }
        }

        return $actorReuseDetails;
    }

    /**
     * Build a single reuse-details entry from actor data.
     */
    private function buildReuseEntry(array $actorData, string $suffixText): array
    {
        $actorData['who'] = $actorData['who'] ?? 'other';

        $label = $actorData['name'] ?? '';

        if (isset($actorData['type']) && $actorData['type'] === 'trust') {
            $actorData['company'] = $label;
        } elseif (is_array($label)) {
            $label = ($label['first'] ?? '') . ' ' . ($label['last'] ?? '');
        }

        // Filter to only allowed keys
        $allowedKeys = [
            'name', 'number', 'otherNames', 'address', 'dob', 'email',
            'case', 'phone', 'who', 'company', 'type', 'canSign',
        ];
        $actorData = array_intersect_key($actorData, array_flip($allowedKeys));

        return [
            'label' => trim($label . ' ' . $suffixText),
            'data' => $this->flattenData($actorData),
        ];
    }

    /**
     * Flatten nested model data into form-compatible flat key format.
     */
    private function flattenData(array $modelData): array
    {
        $formData = [];

        foreach ($modelData as $l1 => $l2) {
            if (is_array($l2)) {
                foreach ($l2 as $name => $l3) {
                    if ($l1 === 'dob') {
                        $dob = new \DateTime($l3);
                        $formData['dob-date'] = [
                            'day'   => $dob->format('d'),
                            'month' => $dob->format('m'),
                            'year'  => $dob->format('Y'),
                        ];
                    } else {
                        $formData[$l1 . '-' . $name] = $l3;
                    }
                }
            } else {
                $formData[$l1] = $l2;
            }
        }

        return $formData;
    }
}
