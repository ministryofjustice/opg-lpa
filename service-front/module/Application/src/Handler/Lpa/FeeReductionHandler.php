<?php

declare(strict_types=1);

namespace Application\Handler\Lpa;

use Application\Handler\Traits\CommonTemplateVariablesTrait;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\View\Helper\Traits\MoneyFormatterTrait;
use Fig\Http\Message\RequestMethodInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Form\Element;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class FeeReductionHandler implements RequestHandlerInterface
{
    use CommonTemplateVariablesTrait;
    use MoneyFormatterTrait;

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

        /** @var \Application\Form\Lpa\FeeReductionForm $form */
        $form = $this->formElementManager->get('Application\Form\Lpa\FeeReductionForm', [
            'lpa' => $lpa,
        ]);

        $existingLpaPayment = $lpa->payment;

        $isPost = strtoupper($request->getMethod()) === RequestMethodInterface::METHOD_POST;

        if (!$isPost && $existingLpaPayment instanceof Payment) {
            $reductionOptionsValue = $this->determineExistingSelection($existingLpaPayment);

            $form->bind([
                'reductionOptions' => $reductionOptionsValue,
            ]);
        }

        if ($isPost) {
            $postData = $request->getParsedBody() ?? [];
            if (!is_array($postData)) {
                $postData = [];
            }

            $form->setData($postData);

            if ($form->isValid()) {
                /** @var array $formData */
                $formData = $form->getData();
                $selectedOption = $formData['reductionOptions'];

                $lpa->payment = $this->createPaymentFromOption($selectedOption);

                if ($this->paymentHasChanged($existingLpaPayment, $lpa->payment)) {
                    Calculator::calculate($lpa);

                    if (!$this->lpaApplicationService->setPayment($lpa, $lpa->payment)) {
                        throw new RuntimeException(
                            'API client failed to set payment details for id: '
                            . $lpa->id . ' in FeeReductionHandler'
                        );
                    }
                }

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
        }

        $isRepeatApplication = ($lpa->repeatCaseNumber !== null);
        $reductionOptions = $this->buildReductionOptions($form, $isRepeatApplication);

        $html = $this->renderer->render(
            'application/authenticated/lpa/fee-reduction/index.twig',
            array_merge(
                $this->getTemplateVariables($request),
                [
                    'form' => $form,
                    'reductionOptions' => $reductionOptions,
                ]
            )
        );

        return new HtmlResponse($html);
    }

    private function determineExistingSelection(Payment $payment): string
    {
        if ($payment->reducedFeeReceivesBenefits === true && $payment->reducedFeeAwardedDamages === true) {
            return 'reducedFeeReceivesBenefits';
        }

        if ($payment->reducedFeeUniversalCredit === true) {
            return 'reducedFeeUniversalCredit';
        }

        if ($payment->reducedFeeLowIncome === true) {
            return 'reducedFeeLowIncome';
        }

        return 'notApply';
    }

    /**
     * @var array<string, array<string, bool|null>>
     */
    private static array $paymentOptionMap = [
        'reducedFeeReceivesBenefits' => [
            'reducedFeeReceivesBenefits' => true,
            'reducedFeeAwardedDamages'   => true,
            'reducedFeeLowIncome'        => null,
            'reducedFeeUniversalCredit'  => null,
        ],
        'reducedFeeUniversalCredit' => [
            'reducedFeeReceivesBenefits' => false,
            'reducedFeeAwardedDamages'   => null,
            'reducedFeeLowIncome'        => false,
            'reducedFeeUniversalCredit'  => true,
        ],
        'reducedFeeLowIncome' => [
            'reducedFeeReceivesBenefits' => false,
            'reducedFeeAwardedDamages'   => null,
            'reducedFeeLowIncome'        => true,
            'reducedFeeUniversalCredit'  => false,
        ],
        'notApply' => [
            'reducedFeeReceivesBenefits' => null,
            'reducedFeeAwardedDamages'   => null,
            'reducedFeeLowIncome'        => null,
            'reducedFeeUniversalCredit'  => null,
        ],
    ];

    private function createPaymentFromOption(string $option): Payment
    {
        $data = self::$paymentOptionMap[$option] ?? self::$paymentOptionMap['notApply'];
        return new Payment($data);
    }

    private function paymentHasChanged(?Payment $existing, Payment $new): bool
    {
        if ($existing === null) {
            return true;
        }

        return $existing->reducedFeeReceivesBenefits != $new->reducedFeeReceivesBenefits
            || $existing->reducedFeeAwardedDamages != $new->reducedFeeAwardedDamages
            || $existing->reducedFeeLowIncome != $new->reducedFeeLowIncome
            || $existing->reducedFeeUniversalCredit != $new->reducedFeeUniversalCredit;
    }

    /**
     * @return array<string, Element>
     */
    private function buildReductionOptions(
        \Application\Form\Lpa\FeeReductionForm $form,
        bool $isRepeatApplication,
    ): array {
        $reduction = $form->get('reductionOptions');
        $valueOptions = $reduction->getOptions()['value_options'];
        $currentValue = $reduction->getValue();

        $benefitsFee = $this->formatMoney(Calculator::getBenefitsFee());
        $lowIncomeFee = $this->formatMoney(Calculator::getLowIncomeFee($isRepeatApplication));
        $fullFee = $this->formatMoney(Calculator::getFullFee($isRepeatApplication));

        $options = [
            'reducedFeeReceivesBenefits' => [
                'id' => 'reductionOptions',
                'value' => $valueOptions['reducedFeeReceivesBenefits']['value'],
                'label' => "The donor currently claims one of <a class=\"js-guidance\" "
                    . "href=\"/guide#topic-fees-reductions-and-exemptions\" "
                    . "data-analytics-click=\"page:link:help: these means-tested benefits\">"
                    . "these means-tested benefits</a> and has not been awarded personal injury damages "
                    . "of more than £16,000<br><strong class='bold-small'>Fee: £"
                    . $benefitsFee . "</strong>",
                'data-cy' => 'reducedFeeReceivesBenefits',
            ],
            'reducedFeeUniversalCredit' => [
                'id' => 'reducedFeeUniversalCredit',
                'value' => $valueOptions['reducedFeeUniversalCredit']['value'],
                'label' => "The donor receives Universal Credit<br>"
                    . "<strong class='bold-small'>We'll contact you about the fee</strong>",
                'data-cy' => 'reducedFeeUniversalCredit',
            ],
            'reducedFeeLowIncome' => [
                'id' => 'reducedFeeLowIncome',
                'value' => $valueOptions['reducedFeeLowIncome']['value'],
                'label' => "The donor currently has an income of less than £12,000 a year before tax"
                    . "<br><strong class='bold-small'>Fee: £" . $lowIncomeFee . "</strong>",
                'data-cy' => 'reducedFeeLowIncome',
            ],
            'notApply' => [
                'id' => 'notApply',
                'value' => $valueOptions['notApply']['value'],
                'label' => "The donor is not applying for a reduced fee<br>"
                    . "<strong class='bold-small'>Fee: £" . $fullFee . "</strong>",
                'data-cy' => 'notApply',
            ],
        ];

        $reductionOptions = [];

        foreach ($options as $key => $config) {
            $element = new Element('reductionOptions', ['label' => $config['label']]);
            $attributes = [
                'type' => 'radio',
                'id' => $config['id'],
                'value' => (string) $config['value'],
                'data-cy' => $config['data-cy'],
            ];

            if ($currentValue == $config['value']) {
                $attributes['checked'] = 'checked';
            }

            $element->setAttributes($attributes);
            $reductionOptions[$key] = $element;
        }

        return $reductionOptions;
    }
}
