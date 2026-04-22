<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\EmailAddress;
use MakeShared\DataModel\Common\PhoneNumber;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class CorrespondentHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;

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

        /** @var \Application\Form\Lpa\CorrespondenceForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\CorrespondenceForm', [
            'lpa' => $lpa,
        ]);
        $form->setAttribute('action', $this->urlHelper->generate('lpa/correspondent', [
            'lpa-id' => $lpa->id,
        ]));

        // Determine some details about the existing correspondent
        $correspondent = $this->getLpaCorrespondent($lpa);

        if ($correspondent === null) {
            throw new RuntimeException('Unable to determine correspondent for LPA: ' . $lpa->id);
        }

        $correspondentEmailAddress = (
            $correspondent->email instanceof EmailAddress ? $correspondent->email : null
        );
        $correspondentPhoneNumber = (
            isset($correspondent->phone) && $correspondent->phone instanceof PhoneNumber
                ? $correspondent->phone->number : null
        );

        if (strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                // Set the initial correspondent data
                $correspondentData = [];

                if ($correspondent instanceof Correspondence) {
                    $correspondentData = array_replace_recursive($correspondent->toArray(), $correspondentData);
                    unset($correspondentData['email']);
                    unset($correspondentData['phone']);
                } else {
                    $correspondentData['who'] = (
                        $correspondent instanceof Donor ? Correspondence::WHO_DONOR : Correspondence::WHO_ATTORNEY
                    );
                    $correspondentData['name'] = (
                        $correspondent instanceof TrustCorporation ? null : $correspondent->name?->toArray()
                    );
                    $correspondentData['company'] = (
                        $correspondent instanceof TrustCorporation ? $correspondent->name : null
                    );
                    $correspondentData['address'] = $correspondent->address?->toArray();
                }

                $correspondent = new Correspondence($correspondentData);

                /** @var array $formData */
                $formData = $form->getData();

                $correspondent->contactInWelsh = (bool)$formData['contactInWelsh'];

                $correspondenceFormData = $formData['correspondence'];

                $correspondent->contactByPost = (bool)$correspondenceFormData['contactByPost'];

                if ($correspondenceFormData['contactByEmail']) {
                    $correspondent->setEmail(new EmailAddress([
                        'address' => strtolower($correspondenceFormData['email-address'])
                    ]));
                }

                if ($correspondenceFormData['contactByPhone']) {
                    $correspondent->setPhone(new PhoneNumber([
                        'number' => $correspondenceFormData['phone-number']
                    ]));
                }

                if (!$this->lpaApplicationService->setCorrespondent($lpa, $correspondent)) {
                    throw new RuntimeException('API client failed to set correspondent for id: ' . $lpa->id);
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
            $form->bind([
                'contactInWelsh' => (
                    isset($correspondent->contactInWelsh) ? $correspondent->contactInWelsh : false
                ),
                'correspondence' => [
                    'contactByEmail' => !is_null($correspondentEmailAddress),
                    'email-address' => $correspondentEmailAddress,
                    'contactByPhone' => !is_null($correspondentPhoneNumber),
                    'phone-number' => $correspondentPhoneNumber,
                    'contactByPost' => (
                        isset($correspondent->contactByPost) ? $correspondent->contactByPost : false
                    ),
                ]
            ]);
        }

        // Construct the correspondent's name to display
        $correspondentName = (string) $correspondent->name;

        if (isset($correspondent->company) && !empty($correspondent->company)) {
            $correspondentName .= (empty($correspondentName) ? '' : ', ');
            $correspondentName .= $correspondent->company;
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/correspondent/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form'                 => $form,
                    'correspondentName'    => $correspondentName,
                    'correspondentAddress' => $correspondent->address,
                    'contactEmail'         => $correspondentEmailAddress,
                    'contactPhone'         => $correspondentPhoneNumber,
                    'changeRoute'          => $this->urlHelper->generate(
                        $currentRoute . '/edit',
                        ['lpa-id' => $lpa->id]
                    ),
                    'allowEditButton'      => $this->allowCorrespondentToBeEdited($lpa),
                ]
            )
        );

        return new HtmlResponse($html);
    }

    /**
     * Get the best correspondent actor for the LPA.
     *
     * @return \MakeShared\DataModel\AbstractData|null
     */
    private function getLpaCorrespondent(Lpa $lpa)
    {
        $lpaDocument = $lpa->document;
        $correspondent = $lpaDocument->correspondent;

        if (is_null($correspondent)) {
            if ($lpaDocument->whoIsRegistering == Correspondence::WHO_DONOR) {
                $correspondent = $lpaDocument->donor;
            } else {
                /** @var array|string|null $whoIsRegistering */
                $whoIsRegistering = $lpaDocument->whoIsRegistering;

                if (is_array($whoIsRegistering)) {
                    $firstAttorneyId = array_values($whoIsRegistering)[0];

                    foreach ($lpaDocument->primaryAttorneys as $attorney) {
                        if ($attorney->id == $firstAttorneyId) {
                            $correspondent = $attorney;
                            break;
                        }
                    }
                }
            }
        }

        return $correspondent;
    }

    /**
     * Determine if the current correspondent data can be edited.
     */
    private function allowCorrespondentToBeEdited(Lpa $lpa): bool
    {
        $correspondent = $this->getLpaCorrespondent($lpa);

        if ($correspondent instanceof Correspondence) {
            if (
                $correspondent->who == Correspondence::WHO_OTHER
                || ($correspondent->who == Correspondence::WHO_ATTORNEY && $correspondent->company !== '')
            ) {
                return true;
            }
        } elseif ($correspondent instanceof TrustCorporation) {
            return true;
        }

        return false;
    }
}
