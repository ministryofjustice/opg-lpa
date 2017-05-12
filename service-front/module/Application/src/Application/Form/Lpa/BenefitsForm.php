<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Payment\Payment;

class BenefitsForm extends AbstractLpaForm
{
    protected $formElements = [
        'reducedFeeReceivesBenefits' => [
            'type'      => 'Radio',
            'required'  => true,
            'options'   => [
                'value_options' => [
                    'no' => [
                        'value' => 0,
                    ],
                    'yes' => [
                        'value' => 1,
                    ],
                ],
            ],
        ],
        'reducedFeeAwardedDamages' => [
            'type'      => 'Radio',
            'required'  => true,
            'options'   => [
                'value_options' => [
                    'no-damage' => [
                        'value' => 1,
                    ],
                    'less-than-16k' => [
                        'value' => 1,
                    ],
                    'over-16k' => [
                        'value' => 0,
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
        $this->setName('form-benefits');

        parent::init();
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        $lpa = new Payment([
            'reducedFeeReceivesBenefits' => (bool)$this->data['reducedFeeReceivesBenefits'],
            'reducedFeeAwardedDamages'   => (array_key_exists('reducedFeeAwardedDamages', $this->data) ? (bool)$this->data['reducedFeeAwardedDamages'] : null),
        ]);

        $validation = $lpa->validate(['reducedFeeReceivesBenefits', 'reducedFeeAwardedDamages']);

        $messages = [];

        if ($validation->hasErrors()) {
            $messages = $this->modelValidationMessageConverter($validation);
        }

        return [
            'isValid'  => !$validation->hasErrors(),
            'messages' => $messages,
        ];
    }
}
