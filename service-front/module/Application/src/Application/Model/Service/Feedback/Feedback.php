<?php
namespace Application\Model\Service\Feedback;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Feedback implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
    
    public function sendMail($data) {
        
    }
}
