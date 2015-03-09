<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller\General;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use Application\Form\General\FeedbackForm;

class FeedbackController extends AbstractBaseController
{
    public function indexAction()
    {
        $feedbackService = $this->getServiceLocator()->get('Feedback');
        
        $form = new FeedbackForm();
        
        $model = new ViewModel(['form'=>$form]);
        
        $model->setTemplate('application/feedback/index.phtml');
        
        return $model;
    }
}
