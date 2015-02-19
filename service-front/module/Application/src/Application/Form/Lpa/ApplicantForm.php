<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;

class ApplicantForm extends AbstractForm
{
    protected $lpa;
    
    protected $formElements = [
            'whoIsRegistering' => [
                    'type' => 'Zend\Form\Element\Radio',
                    'options' => [
                            'value_options' => [
                                    'donor' => [
                                            'value' => 'donor', 
                                    ],
                                    'attorney' => [
                                    ],
                            ],
                    ],
            ],
            'submit' => [
                    'type' => 'Zend\Form\Element\Submit',
            ],
    ];
    
    public function __construct (Lpa $lpa, $formName = 'applicant')
    {
        $this->lpa = $lpa;
        
        $this->formElements['whoIsRegistering']['options']['value_options']['attorney']['value'] = implode(',', array_map(function($attorney){
            return $attorney->id;
        }, $lpa->document->primaryAttorneys));
        
        if((count($lpa->document->primaryAttorneys) > 1) && ($lpa->document->primaryAttorneyDecisions->how != PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)) {
            $this->setAttorneyList();
        }
        
        parent::__construct($formName);
        
    }
    
    public function validateByModel()
    {
        $lpa = new Lpa();
        $lpa->document = clone $this->lpa->document;
        
        if(isset($this->data['attorneyList'])) {
            $lpa->document->whoIsRegistering = $this->data['attorneyList'];
        }
        else {
            if($this->data['whoIsRegistering'] == 'donor') {
                $lpa->document->whoIsRegistering = $this->data['whoIsRegistering'];
            }
            else {
                $lpa->document->whoIsRegistering = explode(',', $this->data['whoIsRegistering']);
            }
        }
        
        $validation = $lpa->document->validate(['whoIsRegistering']);
        
        if(count($validation) == 0) {
            return ['isValid'=>true, 'messages' => []];
        }
        else {
            return [
                    'isValid'=>false,
                    'messages' => $this->modelValidationMessageConverter($validation),
            ];
        }
    }
    
    public function setAttorneyList()
    {
        $this->formElements += [
                'attorneyList' => [
                        'type' => 'Zend\Form\Element\MultiCheckbox',
                        'options' => [
                                'value_options' => [],
                        ],
                ],
        ];
        
        foreach($this->lpa->document->primaryAttorneys as $attorney) {
            $this->formElements['attorneyList']['options']['value_options'][$attorney->id] = [
                    'label' => $attorney->name->__toString(),
                    'value' => $attorney->id,
                    'label_attributes' => [
                            'for' => 'attorney-'.$attorney->id,
                    ],
                    'attributes' => [
                            'id' => 'attorney-'.$attorney->id,
                    ]
            ];
        }
    }
}
