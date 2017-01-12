<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Document;

class DateCheckForm extends AbstractForm
{
    protected $lpa;

    protected $formElements;

    public function __construct($name, $options)
    {
        if (array_key_exists('lpa', $options)) {
            $this->lpa = $options['lpa'];
            unset($options['lpa']);
        }

        parent::__construct('form-date-checker', $options);
    }

    public function init()
    {
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
                'type'       => 'Text',
                'attributes' => [
                    'id' => $dateElementName,
                ],
                'required'   => true,
                'validators' => [
                    [
                        'name'    => 'Date',
                        'options' => [
                            'format' => 'd/m/Y',
                        ],
                    ],
                    [
                        'name'    => 'StringLength',
                        'options' => [
                            'min' => 10,
                            'max' => 10,
                        ],
                    ],
                ],
            ];
        }

        //  Add the submit input
        $this->formElements['submit'] = [
            'type' => 'Submit',
        ];

        parent::init();
    }

    /**
     * Validate form input data through model validators.
     */
    public function validateByModel()
    {
        return ['isValid' => true];
    }
}
