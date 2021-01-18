<?php
namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class MoneyFormat extends AbstractHelper
{
    public function __invoke( $amount )
    {
        // If the amount it a round number, just output pounds. Otherwise include pence.
        return ( floor( $amount ) == $amount ) ? $amount : number_format( $amount, 2);
    }
}
