<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class FormErrorTextExchange extends AbstractHelper
{
    private $elementMap = [
        'whoIsRegistering' => [
            'allowed-values:donor,Array' => 'You must select either the donor one or more of the attorneys'
        ],
    ];
    
    private $commonMap = [
        'cannot-be-blank' => 'Please enter a value for this field'
    ];
    
    public function __invoke($form)
    {
        foreach ($form->getElements() as $element) {
            foreach ($element->getMessages() as &$elementMessage) {
                
                $name = $element->getName();
                
                if (array_key_exists($name, $this->elementMap)) {
                    $messageMap = $this->elementMap[$name];
                    
                    if (array_key_exists($elementMessage, $messageMap)) {
                        $elementMessage = $messageMap[$elementMessage];
                        continue;
                    }
                }
                
                if (array_key_exists($elementMessage, $this->commonMap)) {
                    $elementMessage = $this->commonMap[$elementMessage];
                    continue;
                }
            }
        }
        
        return $form;
    }
}