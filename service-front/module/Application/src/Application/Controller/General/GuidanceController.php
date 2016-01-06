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

class GuidanceController extends AbstractBaseController
{
    public function indexAction()
    {
        $guidanceService = $this->getServiceLocator()->get('Guidance');
        
        $model = new ViewModel($guidanceService->parseMarkdown());
        
        $model->setTemplate('guidance/opg-help-system.twig');
        
        if ($this->request->isXmlHttpRequest()) {
            // if this is accessed via ajax request, disable layout, and return the core text content
            $model->setTerminal(true);
        }
        
        return $model;
    }
}
