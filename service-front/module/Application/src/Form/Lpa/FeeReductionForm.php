<?php

namespace Application\Form\Lpa;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */

class FeeReductionForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'reductionOptions' => [
            'type'      => 'Laminas\Form\Element\Radio',
            'attributes' => ['div-attributes' => ['class' => 'govuk-radios__item']],
            'required'  => true,
            'options'   => [
                'value_options' => [
                    'reducedFeeReceivesBenefits' => [
                        'value' => 'reducedFeeReceivesBenefits',
                    ],
                    'reducedFeeUniversalCredit' => [
                        'value' => 'reducedFeeUniversalCredit',
                    ],
                    'reducedFeeLowIncome' => [
                        'value' => 'reducedFeeLowIncome',
                    ],
                    'notApply' => [
                        'value' => 'notApply',
                    ],
                ],
            ],
        ],
    ];

    public function init()
    {
        $this->setName('form-fee-reduction');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        return [
            'isValid' => true,
            'messages' => []
        ];
    }
}
