<?php
namespace Application\Model\Service\Payment;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Feedback implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
    
    public function sendMail($options) {
        
    }
}
