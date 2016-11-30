<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class MoneyFormat extends AbstractHelper
{
    public function __invoke( $amount )
    {
        // If the amount it a round number, just output pounds. Otherwise include pence.
        return ( floor( $amount ) == $amount ) ? $amount : money_format('%i', $amount);
    }
}
