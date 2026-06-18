<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

/**
 * @template T
 * @template-extends AbstractMainFlowForm<T>
 */
class ApplicantForm extends AbstractMainFlowForm
{
    protected $formElements = [
        'whoIsRegistering' => [
            'type'    => 'Laminas\Form\Element\Radio',
            'attributes' => [
                'id'             => 'whoIsRegistering',
                'class'          => 'govuk-radios__input',
                'div-attributes' => ['class' => 'govuk-radios__item'],
            ],
            'options' => [
                'value_options' => [
                    'donor'    => ['value' => 'donor'],
                    'attorney' => [],
                ],
            ],
        ],
    ];

    public function init()
    {
        $this->setName('form-applicant');

        $this->formElements['whoIsRegistering']['options']['value_options']['attorney']['value'] =
            implode(',', array_map(function ($attorney) {
                return $attorney->id;
            }, $this->lpa->document->primaryAttorneys));

        if (
            count($this->lpa->document->primaryAttorneys) > 1 &&
            $this->lpa->document->primaryAttorneyDecisions->how != PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY
        ) {
            $this->setAttorneyList();
        }

        parent::init();
    }

    private function setAttorneyList(): void
    {
        $this->formElements += [
            'attorneyList' => [
                'type'    => 'MultiCheckbox',
                'options' => [
                    'value_options' => [],
                ],
            ],
        ];

        foreach ($this->lpa->document->primaryAttorneys as $attorney) {
            $this->formElements['attorneyList']['options']['value_options'][$attorney->id] = [
                'label'            => (($attorney instanceof Human) ? (string)$attorney->name : $attorney->name),
                'value'            => $attorney->id,
                'label_attributes' => ['for' => 'attorney-' . $attorney->id],
                'attributes'       => [
                    'id'             => 'attorney-' . $attorney->id,
                    'class'          => 'govuk-checkboxes__input',
                    'div-attributes' => ['class' => 'govuk-checkboxes__item'],
                ],
            ];
        }
    }

    /**
     * @return (array|bool|mixed)[]
     *
     * @psalm-return array{isValid: bool, messages: array<never, never>|mixed}
     */
    protected function validateByModel()
    {
        $lpaDocument = clone $this->lpa->document;

        if (isset($this->data['whoIsRegistering']) && $this->data['whoIsRegistering'] == 'donor') {
            $lpaDocument->whoIsRegistering = $this->data['whoIsRegistering'];
        } elseif (
            count($lpaDocument->primaryAttorneys) > 1 &&
            $lpaDocument->primaryAttorneyDecisions->how != PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY
        ) {
            if (array_key_exists('attorneyList', $this->data)) {
                $lpaDocument->whoIsRegistering = $this->data['attorneyList'];
            } else {
                $lpaDocument->whoIsRegistering = [];
            }
        } else {
            $lpaDocument->whoIsRegistering = (isset($this->data['whoIsRegistering'])
                ? explode(',', $this->data['whoIsRegistering']) : []);
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
