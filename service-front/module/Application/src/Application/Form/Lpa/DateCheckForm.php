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
    
    public function __construct (Lpa $lpa, $formName = 'type-form')
    {
        $this->lpa = $lpa;
        
        parent::__construct($formName);
        
    }
    
   /**
    * Validate form input data through model validators.
    */
    public function validateByModel()
    {
        
    }

}
