<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class RepeatApplicationHandler implements RequestHandlerInterface
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

        /** @var \Application\Form\Lpa\RepeatApplicationForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\RepeatApplicationForm', [
            'lpa' => $lpa,
        ]);

        $isPost = strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST;

        if ($isPost) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if (($postData['isRepeatApplication'] ?? '') !== 'is-repeat') {
                $form->setValidationGroup(['isRepeatApplication']);
            }

            if ($form->isValid()) {
                /** @var array $formData */
                $formData = $form->getData();

                $previousRepeatCaseNumber = $lpa->repeatCaseNumber;

                if ($formData['isRepeatApplication'] === 'is-repeat') {
                    if ($formData['repeatCaseNumber'] !== $lpa->repeatCaseNumber) {
                        $setOk = $this->lpaApplicationService->setRepeatCaseNumber(
                            $lpa,
                            $formData['repeatCaseNumber']
                        );

                        if ($setOk === false) {
                            throw new RuntimeException(
                                'API client failed to set repeat case number for id: ' . $lpa->id
                            );
                        }
                    }

                    $lpa->repeatCaseNumber = $formData['repeatCaseNumber'];
                } else {
                    if ($lpa->repeatCaseNumber !== null) {
                        $deleteOk = $this->lpaApplicationService->deleteRepeatCaseNumber($lpa);

                        if ($deleteOk === false) {
                            throw new RuntimeException(
                                'API client failed to set repeat case number for id: ' . $lpa->id
                            );
                        }
                    }

                    $lpa->repeatCaseNumber = null;
                }

                if ($lpa->payment instanceof Payment && $lpa->repeatCaseNumber != $previousRepeatCaseNumber) {
                    Calculator::calculate($lpa);

                    if (!$this->lpaApplicationService->setPayment($lpa, $lpa->payment)) {
                        throw new RuntimeException(
                            'API client failed to set payment details for id: '
                            . $lpa->id . ' in RepeatApplicationHandler'
                        );
                    }
                }

                $this->metadata->setRepeatApplicationConfirmed($lpa);

                $isPopup = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';

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
            if (array_key_exists(Lpa::REPEAT_APPLICATION_CONFIRMED, $lpa->metadata)) {
                $form->bind([
                    'isRepeatApplication' => ($lpa->repeatCaseNumber === null) ? 'is-new' : 'is-repeat',
                    'repeatCaseNumber'    => $lpa->repeatCaseNumber,
                ]);
            }
        }

        $html = $this->renderer->render(
            'application/authenticated/lpa/repeat-application/index.twig',
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
