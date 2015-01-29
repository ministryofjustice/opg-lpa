<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;

class PaymentCallbackController extends AbstractLpaController
{
    public function successAction()
    {
        echo __FUNCTION__;
    }
    
    public function failureAction()
    {
        echo __FUNCTION__;
    }
    
    public function cancelAction()
    {
        echo __FUNCTION__;
    }
    
    public function pendingAction()
    {
        echo __FUNCTION__;
    }
}
