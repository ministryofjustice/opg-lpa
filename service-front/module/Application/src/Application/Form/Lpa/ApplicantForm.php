<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Document\Decisions\PrimaryAttorneyDecisions;
use Opg\Lpa\DataModel\Lpa\Document\Attorneys\Human;

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
    
    public function init (Lpa $lpa)
    {
        $this->lpa = $lpa;
        
        // for attorney option, the value is comma delimited attorney ids.
        $this->formElements['whoIsRegistering']['options']['value_options']['attorney']['value'] = implode(',', array_map(function($attorney){
            return $attorney->id;
        }, $lpa->document->primaryAttorneys));
        
        // if number of attorneys are more than 1, and how they make decisions is NOT jointly, user must select which attorney(s) are(is) applicants.  
        if((count($lpa->document->primaryAttorneys) > 1) && ($lpa->document->primaryAttorneyDecisions->how != PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)) {
            $this->setAttorneyList();
        }
        
        $this->setName('applicant');
        
        parent::init();
    }
    
    public function validateByModel()
    {
        $lpa = new Lpa();
        $lpa->document = clone $this->lpa->document;
        if($this->data['whoIsRegistering'] == 'donor') {
            $lpa->document->whoIsRegistering = $this->data['whoIsRegistering'];
        }
        else {
            if((count($lpa->document->primaryAttorneys) > 1) && ($lpa->document->primaryAttorneyDecisions->how != PrimaryAttorneyDecisions::LPA_DECISION_HOW_JOINTLY)) {
                if(array_key_exists('attorneyList', $this->data)) {
                    // this is when at least one of the attorney checkboxes was ticked.
                    $lpa->document->whoIsRegistering = $this->data['attorneyList'];
                }
                else {
                    // this is when NONE of the attorney checkboxes was ticked.
                    $lpa->document->whoIsRegistering = [];
                }
            }
            else {
                // if lpa has only 1 attorney, or has more than 1 attorney and they make decision jointly, user can only select the donor or all attorneys. 
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
                    'label' => (($attorney instanceof Human)?(string)$attorney->name:$attorney->name),
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
