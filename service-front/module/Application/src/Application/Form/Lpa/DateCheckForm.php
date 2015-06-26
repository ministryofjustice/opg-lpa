<?php
namespace Application\Form\Lpa;


class DateCheckForm extends AbstractForm
{
    protected $lpa;
    
    protected $formElements = [
        'sign-date-donor' => [],
        'sign-date-certificate-provider' => [],
        'submit' => [
            'type' => 'Zend\Form\Element\Submit',
        ],
    ];
    
    private $dateValidators = [
        [
            'name' => 'Date',
            'options' => [
                'format' => 'd/m/Y',
            ],
        ],
        [
            'name' => 'StringLength',
            'options' => [
                'min' => 10,
                'max' => 10,
            ],
        ]
    ];

    public function __construct($name, $options)
    {
        if(array_key_exists('lpa', $options)) {
            $this->lpa = $options['lpa'];
            unset($options['lpa']);
        }
    
        parent::__construct($name, $options);
    }
    
    public function init ()
    {
        foreach($this->lpa->document->primaryAttorneys as $idx => $attorney) {
            $this->formElements['sign-date-attorney-' . $idx] = [];
        }
        
        foreach($this->lpa->document->replacementAttorneys as $idx => $attorney) {
            $this->formElements['sign-date-replacement-attorney-' . $idx] = [];
        }
        
        foreach ($this->formElements as $key => &$element) {
            if ($key != 'submit') {
                $element['type'] = 'Zend\Form\Element';
                $element['required'] = true;
                $element['validators'] = $this->dateValidators;
            }
        }
        
        $this->setName('form-date-checker');
        
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
