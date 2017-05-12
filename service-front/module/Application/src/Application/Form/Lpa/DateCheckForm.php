<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Document;

class DateCheckForm extends AbstractLpaForm
{
    protected $formElements = [
        'submit' => [
            'type' => 'Submit',
        ],
    ];

    public function init()
    {
        $this->setName('form-date-checker');

        //  Set up the date element input names
        $dateElementNames = [
            'sign-date-donor',
            'sign-date-certificate-provider',
        ];

        //  If applicable add the life sustaining date
        if ($this->lpa->document->type === Document::LPA_TYPE_HW) {
            $dateElementNames[] = 'sign-date-donor-life-sustaining';
        }

        //  Add a signing date for each attorney
        foreach ($this->lpa->document->primaryAttorneys as $idx => $attorney) {
            $dateElementNames[] = 'sign-date-attorney-' . $idx;
        }

        //  Add a signing date for each replacement attorney
        foreach ($this->lpa->document->replacementAttorneys as $idx => $attorney) {
            $dateElementNames[] = 'sign-date-replacement-attorney-' . $idx;
        }

        //  Loop through to the date element names and add the configuration to the
        foreach ($dateElementNames as $dateElementName) {
            $this->formElements[$dateElementName] = [
                'type'       => 'Application\Form\Fieldset\Dob',
                'attributes' => [
                    'id' => $dateElementName,
                ],
                'required'   => true,
                'validators' => [
                    [
                        'name'    => 'Application\Form\Validator\Date',
                    ],
                ],
            ];
        }

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
            'isValid' => true
        ];
    }
}
