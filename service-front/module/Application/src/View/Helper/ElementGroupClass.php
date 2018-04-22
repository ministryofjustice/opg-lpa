<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\Form\ElementInterface;

class ElementGroupClass extends AbstractHelper
{
    /**
     * @param ElementInterface
     */
    public function __invoke(ElementInterface $formElement)
    {
        $classes = 'group';
        if (count($formElement->getMessages()) > 0) {
            $classes .= ' validation';
        }
        return $classes;
    }
}