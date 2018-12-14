<?php

namespace Application\Form\Lpa;

class FeeReductionForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'reductionOptions' => [
            'type'      => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'required'  => true,
            'options'   => [
                'value_options' => [
                    'reducedFeeReceivesBenefits' => [
                        'value' => 'reducedFeeReceivesBenefits',
                    ],
                    'reducedFeeUniversalCredit' => [
                        'value' => 'reducedFeeUniversalCredit',
                    ],
                    'reducedFeeLowIncome' =>[
                        'value' => 'reducedFeeLowIncome',
                    ],
                    'notApply' => [
                        'value' => 'notApply'
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
