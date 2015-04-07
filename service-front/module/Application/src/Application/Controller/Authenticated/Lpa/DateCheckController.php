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
use Zend\View\Model\ViewModel;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;
use Application\Form\Lpa\FeeForm;
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
use Zend\Session\Container;
use Application\Form\Lpa\DateCheckForm;

class DateCheckController extends AbstractLpaController
{
   
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $lpa = $this->getLpa();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $form = new DateCheckForm($lpa);
        
        if($this->request->isPost()) {
            $post = $this->request->getPost();
            
            // set data for validation
            $form->setData($post);
            
            if($form->isValid()) {
                
            } else {
            }
        } else {
         
        }
        
        return new ViewModel(['form'=>$form]);
    }
}
