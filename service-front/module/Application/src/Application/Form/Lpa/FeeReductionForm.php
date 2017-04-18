<?php

namespace Application\Form\Lpa;

class FeeReductionForm extends AbstractLpaForm
{
    protected $formElements = [
        'reductionOptions' => [
            'type'      => 'Radio',
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
        'submit' => [
            'type' => 'Submit',
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
    public function validateByModel()
    {
        return [
            'isValid' => true,
            'messages' => []
        ];
    }
}
