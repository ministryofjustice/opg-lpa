<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Application\Model\Service\Session\SessionManager;
use Application\Model\Service\User\Details as UserService;
use Application\View\Helper\MoneyFormat;
use Laminas\Form\Element;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\Session\Container;
use Laminas\View\Model\ViewModel;
use MakeShared\DataModel\Lpa\Payment\Calculator;
use MakeShared\DataModel\Lpa\Payment\Payment;
use MakeShared\Logging\LoggerTrait;

class FeeReductionController extends AbstractLpaController
{
    use LoggerTrait;

    /** @var MoneyFormat */
    private $moneyFormat;

    /**
     * Override AbstractLpaController constructor to add in MoneyFormat
     * instance.
     *
     * @param string $lpaId
     * @param AbstractPluginManager $formElementManager
     * @param SessionManager $sessionManager
     * @param AuthenticationService $authenticationService
     * @param array $config
     * @param Container $userDetailsSession
     * @param LpaApplicationService $lpaApplicationService
     * @param UserService $userService
     * @param ReplacementAttorneyCleanup $replacementAttorneyCleanup
     * @param Metadata $metadata
     * @param MoneyFormat $moneyFormat
     */
    public function __construct(
        $lpaId,
        $formElementManager,
        $sessionManager,
        $authenticationService,
        $config,
        $userDetailsSession,
        $lpaApplicationService,
        $userService,
        $replacementAttorneyCleanup,
        $metadata,
        $moneyFormat = null
    ) {
        parent::__construct(
            $lpaId,
            $formElementManager,
            $sessionManager,
            $authenticationService,
            $config,
            $userDetailsSession,
            $lpaApplicationService,
            $userService,
            $replacementAttorneyCleanup,
            $metadata
        );

        if (is_null($moneyFormat)) {
            $moneyFormat = new MoneyFormat();
        }

        $this->moneyFormat = $moneyFormat;
    }

    public function indexAction()
    {
        $lpa = $this->getLpa();

        $form = $this->getFormElementManager()->get('Application\Form\Lpa\FeeReductionForm', [
            'lpa' => $lpa,
        ]);

        $existingLpaPayment = $lpa->payment;

        // If a option has already been selected before...
        if ($existingLpaPayment instanceof Payment) {
            if (
                $existingLpaPayment->reducedFeeReceivesBenefits &&
                $existingLpaPayment->reducedFeeAwardedDamages
            ) {
                $reductionOptionsValue = 'reducedFeeReceivesBenefits';
            } elseif ($existingLpaPayment->reducedFeeUniversalCredit) {
                $reductionOptionsValue = 'reducedFeeUniversalCredit';
            } elseif ($existingLpaPayment->reducedFeeLowIncome) {
                $reductionOptionsValue = 'reducedFeeLowIncome';
            } else {
                $reductionOptionsValue = 'notApply';
            }

            $form->bind([
                'reductionOptions' => $reductionOptionsValue,
            ]);
        }

        $isRepeatApplication = ($lpa->repeatCaseNumber != null);

        $reduction = $form->get('reductionOptions');

        $reductionOptions = [];

        $amount = Calculator::getBenefitsFee();
        $amount = call_user_func($this->moneyFormat, $amount);
        $reductionOptions['reducedFeeReceivesBenefits'] = new Element('reductionOptions', [
            'label' => "The donor currently claims one of <a class=\"js-guidance\" " .
                       "href=\"/guide#topic-fees-reductions-and-exemptions\" " .
                       "data-analytics-click=\"page:link:help: these means-tested benefits\">" .
                       "these means-tested benefits</a> and has not been awarded personal injury damages " .
                       "of more than £16,000<br><strong class='bold-small'>Fee: £" . $amount . "</strong>",
        ]);
        $reductionOptions['reducedFeeReceivesBenefits']->setAttributes([
            'type' => 'radio',
            'id' => 'reductionOptions',
            'value' => $reduction->getOptions()['value_options']['reducedFeeReceivesBenefits']['value'],
            'checked' => (($reduction->getValue() == 'reducedFeeReceivesBenefits') ? 'checked' : null),
            'data-cy' => 'reducedFeeReceivesBenefits',
        ]);

        $reductionOptions['reducedFeeUniversalCredit'] = new Element('reductionOptions', [
            'label' => "The donor receives Universal Credit<br>" .
                       "<strong class='bold-small'>We'll contact you about the fee</strong>",
        ]);
        $reductionOptions['reducedFeeUniversalCredit']->setAttributes([
            'type' => 'radio',
            'id' => 'reducedFeeUniversalCredit',
            'value' => $reduction->getOptions()['value_options']['reducedFeeUniversalCredit']['value'],
            'checked' => (($reduction->getValue() == 'reducedFeeUniversalCredit') ? 'checked' : null),
            'data-cy' => 'reducedFeeUniversalCredit',
        ]);

        $amount = Calculator::getLowIncomeFee($isRepeatApplication);
        $amount = call_user_func($this->moneyFormat, $amount);
        $reductionOptions['reducedFeeLowIncome'] = new Element('reductionOptions', [
            'label' => "The donor currently has an income of less than £12,000 a year before tax" .
                       "<br><strong class='bold-small'>Fee: £" . $amount . "</strong>",
        ]);
        $reductionOptions['reducedFeeLowIncome']->setAttributes([
            'type' => 'radio',
            'id' => 'reducedFeeLowIncome',
            'value' => $reduction->getOptions()['value_options']['reducedFeeLowIncome']['value'],
            'checked' => (($reduction->getValue() == 'reducedFeeLowIncome') ? 'checked' : null),
            'data-cy' => 'reducedFeeLowIncome',
        ]);

        $amount = Calculator::getFullFee($isRepeatApplication);
        $amount = call_user_func($this->moneyFormat, $amount);
        $reductionOptions['notApply'] = new Element('reductionOptions', [
            'label' => "The donor is not applying for a reduced fee<br>" .
                       "<strong class='bold-small'>Fee: £" . $amount . "</strong>",
        ]);
        $reductionOptions['notApply']->setAttributes([
            'type' => 'radio',
            'id' => 'notApply',
            'value' => $reduction->getOptions()['value_options']['notApply']['value'],
            'checked' => (($reduction->getValue() == 'notApply') ? 'checked' : null),
            'data-cy' => 'notApply',
        ]);

        $request = $this->convertRequest();

        if ($request->isPost()) {
            // set data for validation
            $form->setData($request->getPost());

            if ($form->isValid()) {
                // if no applying reduced fee, set payment in LPA including amount.
                switch ($form->getData()['reductionOptions']) {
                    case 'reducedFeeReceivesBenefits':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => true,
                            'reducedFeeAwardedDamages'  => true,
                            'reducedFeeLowIncome'       => null,
                            'reducedFeeUniversalCredit' => null,
                        ]);
                        // payment date will be set by the API when setPayment() is called.
                        break;
                    case 'reducedFeeUniversalCredit':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => false,
                            'reducedFeeAwardedDamages'  => null,
                            'reducedFeeLowIncome'       => false,
                            'reducedFeeUniversalCredit' => true,
                        ]);
                        // payment date will be set by the API when setPayment() is called.
                        break;
                    case 'reducedFeeLowIncome':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => false,
                            'reducedFeeAwardedDamages'  => null,
                            'reducedFeeLowIncome'       => true,
                            'reducedFeeUniversalCredit' => false,
                        ]);
                        break;
                    case 'notApply':
                        $lpa->payment = new Payment([
                            'reducedFeeReceivesBenefits' => null,
                            'reducedFeeAwardedDamages'  => null,
                            'reducedFeeLowIncome'       => null,
                            'reducedFeeUniversalCredit' => null,
                        ]);
                        break;
                }

                //  If there is an existing payment and the selected values have not changed then save the LPA to update
                if (
                    is_null($existingLpaPayment)
                    || $existingLpaPayment->reducedFeeReceivesBenefits != $lpa->payment->reducedFeeReceivesBenefits
                    || $existingLpaPayment->reducedFeeAwardedDamages != $lpa->payment->reducedFeeAwardedDamages
                    || $existingLpaPayment->reducedFeeLowIncome != $lpa->payment->reducedFeeLowIncome
                    || $existingLpaPayment->reducedFeeUniversalCredit != $lpa->payment->reducedFeeUniversalCredit
                ) {
                    // calculate payment amount and set payment in LPA
                    Calculator::calculate($lpa);

                    if (!$this->getLpaApplicationService()->setPayment($lpa, $lpa->payment)) {
                        throw new \RuntimeException(
                            'API client failed to set payment details for id: ' .
                            $lpa->id . ' in FeeReductionController'
                        );
                    }
                }

                return $this->moveToNextRoute();
            }
        }

        return new ViewModel([
            'form' => $form,
            'reductionOptions' => $reductionOptions,
        ]);
    }
}
