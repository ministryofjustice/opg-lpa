<?php
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;

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