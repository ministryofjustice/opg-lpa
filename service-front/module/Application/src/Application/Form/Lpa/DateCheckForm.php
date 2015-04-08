<?php
namespace Application\Form\Lpa;

use Opg\Lpa\DataModel\Lpa\Lpa;

class DateCheckForm extends AbstractForm
{
    /**
     * @var Lpa $lpa
     */
    private $lpa;
    
    protected $formElements = [
        'sign-date-donor' => [
            'type' => 'Zend\Form\Element'
        ],
        'sign-date-certificate-provider' => [
            'type' => 'Zend\Form\Element'
        ],
        'submit' => [
            'type' => 'Zend\Form\Element\Submit',
        ],
    ];
    
    protected $dateValidator = [
        'name' => 'Date',
        'options' => [
            'format' => 'd/m/Y',
        ],
    ];
    
    public function __construct (Lpa $lpa, $formName = 'type-form')
    {
        $this->lpa = $lpa;
        
        $numAttorneys = count($lpa->get('document')->get('primaryAttorneys'));
        for ($i=0; $i<$numAttorneys; $i++) {
            $this->formElements['sign-date-attorney-' . $i] = [
                'type' => 'Zend\Form\Element'
            ];
        }
        
        foreach ($this->formElements as $key => &$element) {
            if ($key != 'submit') {
                $element['validators'] = [$this->dateValidator];
            }
        }
        
        parent::__construct($formName);
        
    }
    
   /**
    * Validate form input data through model validators.
    */
    public function validateByModel()
    {
        
    }

}
