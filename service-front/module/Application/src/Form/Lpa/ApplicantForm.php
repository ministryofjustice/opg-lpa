<?php

namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class ApplicantForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'whoIsRegistering' => [
            'type' => 'Radio',
            'attributes' => ['div-attributes' => ['class' => 'multiple-choice']],
            'options' => [
                'value_options' => [
                    'donor' => [
                        'value' => 'donor',
                    ],
                    'attorney' => [
                        'label_attributes' => [
                            'for' => 'attorney_option_radio',
                        ],
                        'attributes' => [
                            'id' => 'attorney_option_radio',
                        ]
                    ],
                ],
            ],
        ],
    ];

    public function init()
    {
        $this->setName('form-applicant');

        // for attorney option, the value is comma delimited attorney ids.
        $this->formElements['whoIsRegistering']['options']['value_options']['attorney']['value'] = implode(',', array_map(function ($attorney) {
            return $attorney->id;
        }, $this->lpa->document->primaryAttorneys));

        // if number of attorneys are more than 1, and how they make decisions is NOT jointly, user must select which attorney(s) are(is) applicants.
        if (count($this->lpa->document->primaryAttorneys) > 1 && $this->lpa->document->primaryAttorneyDecisions->how != PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY) {
            $this->setAttorneyList();
        }

        parent::init();
    }

    private function setAttorneyList()
    {
        $this->formElements += [
            'attorneyList' => [
                'type' => 'MultiCheckbox',
                'options' => [
                    'value_options' => [],
                ],
            ],
        ];

        foreach ($this->lpa->document->primaryAttorneys as $attorney) {
            $this->formElements['attorneyList']['options']['value_options'][$attorney->id] = [
                'label' => (($attorney instanceof Human)?(string)$attorney->name:$attorney->name),
                'value' => $attorney->id,
                'label_attributes' => [
                    'for' => 'attorney-'.$attorney->id,
                ],
                'attributes' => [
                    'id' => 'attorney-'.$attorney->id,
                    'div-attributes' => ['class' => 'multiple-choice'],
                ],
            ];
        }
    }

    /**
     * Validate form input data through model validators
     *
     * @return array
     */
    protected function validateByModel()
    {
        $lpaDocument = clone $this->lpa->document;

        if (isset($this->data['whoIsRegistering']) && $this->data['whoIsRegistering'] == 'donor') {
            $lpaDocument->whoIsRegistering = $this->data['whoIsRegistering'];
        } elseif (count($lpaDocument->primaryAttorneys) > 1 && $lpaDocument->primaryAttorneyDecisions->how != PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY) {
            if (array_key_exists('attorneyList', $this->data)) {
                // this is when at least one of the attorney checkboxes was ticked.
                $lpaDocument->whoIsRegistering = $this->data['attorneyList'];
            } else {
                // this is when NONE of the attorney checkboxes was ticked.
                $lpaDocument->whoIsRegistering = [];
            }
        } else {
            // if lpa has only 1 attorney, or has more than 1 attorney and they make decision jointly, user can only select the donor or all attorneys.
            $lpaDocument->whoIsRegistering = (isset($this->data['whoIsRegistering']) ? explode(',', $this->data['whoIsRegistering']) : []);
        }

        $validation = $lpaDocument->validate(['whoIsRegistering']);

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
