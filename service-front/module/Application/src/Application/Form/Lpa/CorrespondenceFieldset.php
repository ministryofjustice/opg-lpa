<?php
namespace Application\Form\Lpa;

use Zend\Form\Fieldset;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Opg\Lpa\DataModel\Lpa\Document\Correspondence;

class CorrespondenceFieldset extends Fieldset //implements InputFilterProviderInterface
{
    
    public function __construct()
    {
        parent::__construct('fieldset-correspondence');
        $this->setHydrator(new ClassMethodsHydrator(false))
            ->setObject(new Correspondence());
        
        $this->add([
                'name' => 'contactByEmail',
                'type' => 'Zend\Form\Element\Checkbox',
                'options' => [
                        'checked_value' => true,
                        'unchecked_value' => false,
                ],
        ]);
        
        $this->add([
                'name' => 'contactByPhone',
                'type' => 'Zend\Form\Element\Checkbox',
                'options' => [
                        'checked_value' => true,
                        'unchecked_value' => false,
                ],
        ]);
        
        $this->add([
                'name' => 'contactByPost',
                'type' => 'Zend\Form\Element\Checkbox',
                'options' => [
                        'checked_value' => true,
                        'unchecked_value' => false,
                ],
        ]);
        
        $this->add([
                'name' => 'contactInWelsh',
                'type' => 'Zend\Form\Element\Checkbox',
                'options' => [
                        'checked_value' => true,
                        'unchecked_value' => false,
                ],
        ]);

        $this->add([
            'name' => 'email-address',
            'type' => 'Zend\Form\Element\Email',
        ]);

        $this->add([
            'name' => 'phone-number',
            'type' => 'Zend\Form\Element\Text',
        ]);
        
    }
    
    public function setMessages($messages)
    {
        $this->messages = $messages;
        parent::setMessages($messages);
    }
    
    public function getMessages()
    {
        return $this->messages;
    }
}
