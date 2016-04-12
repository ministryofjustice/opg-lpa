<?php
namespace Application\Form\Fieldset;

use Zend\Form\Fieldset;

class Dob extends Fieldset
{
    public function __construct($name = null, $options = array())
    {
        parent::__construct($name, $options);
    
        $this->add([
            'name' => 'day',
            'type' => 'Text',
            
        ]);
        
        $this->add([
            'name' => 'month',
            'type' => 'Text',
        ]);
        
        $this->add([
            'name' => 'year',
            'type' => 'Text',
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
