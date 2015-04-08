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
use Application\Form\Lpa\DateCheckForm;
use Application\Model\Service\Signatures\DateCheck;

class DateCheckController extends AbstractLpaController
{
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $lpa = $this->getLpa();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $attorneyNames = [];
        foreach ($lpa->get('document')->get('primaryAttorneys') as $attorney) {
            $attorneyNames[] = $attorney->get('name');
        }

        $form = new DateCheckForm($lpa);
        
        $viewParams = [
            'donorName' => $lpa->get('document')->get('donor')->get('name'),
            'certificateProviderName' => $lpa->get('document')->get('certificateProvider')->get('name'),
            'attorneyNames' => $attorneyNames,
        ];
        
        if($this->request->isPost()) {
            
            $post = $this->request->getPost();
            
            $form->setData($post);
            
            if($form->isValid()) {
                $viewParams['datesAreOk'] = DateCheck::checkDates($lpa);
            }
        }
                
        $viewParams['form'] = $form;
        
        return new ViewModel($viewParams);
    }
}
