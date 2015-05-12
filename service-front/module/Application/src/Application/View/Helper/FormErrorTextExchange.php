<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class FormErrorTextExchange extends AbstractHelper
{
    /**
     * @var array - Common Map - generic transformations for common messages
     */
    private $commonMap = [
        'cannot-be-blank' => 'Please enter a value for this field'
    ];
    
    /**
     * @var array Override Map - Specific transformations for named fields
     */
    private $overrideMap = [
        'whoIsRegistering' => [
            'allowed-values:donor,Array' => 'You must select either the donor one or more of the attorneys'
        ],
    ];
    
    
    /**
     * Look at each element message on the form. If a transform message exists
     * in the override map then replace the message with its override. If no 
     * override message exists, see if there is a transformation in the common map.
     *  
     * @param Form $form
     * @return Form
     */
    public function __invoke($form)
    {
        foreach ($form->getElements() as $element) {
            foreach ($element->getMessages() as &$elementMessage) {
                
                $name = $element->getName();
                
                if (array_key_exists($name, $this->overrideMap)) {
                    $messageMap = $this->overrideMap[$name];
                    
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
