<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class FormErrorTextExchange extends AbstractHelper
{
    /**
     * Catch-all transformations, ignorant of field name
     * 
     * @var array - Common Generic Map
     */
    private $commonMap = [
        'cannot-be-blank' => 'Please enter a value for this field',
        'invalid-email-address' => 'Please enter a valid email address',
        'invalid-phone-number' => 'Please enter a valid phone number',
    ];
    
    /**
     * Generic transformations for named fields
     * 
     * $var array - Common Field Map
     */
    private $commonFieldMap = [
        'address-postcode' => [
            'must-be-greater-than-or-equal:5' => 'Postcode must be at least five characters',
        ],
        'name-title' => [
            'must-be-less-than-or-equal:5' => 'Title must be five letters or fewer - please abbreviate, if necessary',
        ]
    ];
    
    /**
     * Look at each element message on the form. If a transform message exists
     * in the override map then replace the message with its override. If no 
     * override message exists, see if there is a transformation in the common map.
     * 
     * The override map is merged with a generic override map which provides 
     * messages for common field names.
     *  
     * @param Form $form
     * @return Form
     */
    public function __invoke($form, $overrideMap)
    {
        $overrideMap = array_merge_recursive(
            $this->commonFieldMap,
            $overrideMap
        );
        
        foreach ($form->getElements() as $element) {
            
            $name = $element->getName();
            
            if (array_key_exists($name, $overrideMap)) {
                $elementMap = $overrideMap[$name];
            } else {
                $elementMap = [];
            }
            
            $messages = $element->getMessages();
            
            foreach ($messages as &$elementMessage) {

                if (array_key_exists($elementMessage, $elementMap)) {
                    $elementMessage = $elementMap[$elementMessage];
                } elseif (array_key_exists($elementMessage, $this->commonMap)) {
                    $elementMessage = $this->commonMap[$elementMessage];
                }
               
            }
            
            $element->setMessages($messages);
        }

        return $form;
    }
}
