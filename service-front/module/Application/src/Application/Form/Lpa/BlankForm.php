<?php
namespace Application\Form\Lpa;

class BlankForm extends AbstractActorForm
{
    protected $formElements = [];
    
    public function init ()
    {
        $this->setName('form-blank');
        
        parent::init();
    }
    
    public function validateByModel()
    {
        return ['isValid'=>true, 'messages' => []];
    }
}
