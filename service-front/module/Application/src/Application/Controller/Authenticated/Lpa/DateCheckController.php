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
use Application\Model\Service\Signatures\DateCheck;

class DateCheckController extends AbstractLpaController
{
    protected $contentHeader = 'registration-partial.phtml';
    
    public function indexAction()
    {
        $lpa = $this->getLpa();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\DateCheckForm', ['lpa'=>$lpa]);
        
        $viewParams = [];
        
        if($this->request->isPost()) {
            
            $post = $this->request->getPost();
            
            $form->setData($post);
            
            $postArray = $post->toArray();

            if($form->isValid()) {
                $attorneySignatureDates = [];
                foreach($postArray as $name => $date) {
                    if(preg_match('/sign-date-(attorney|replacement-attorney)-\d/', $name)) {
                        $attorneySignatureDates[] = $date;
                    }
                }
                
                $viewParams['datesAreOk'] = DateCheck::checkDates([
                    'donor' => $postArray['sign-date-donor'],
                    'certificate-provider' => $postArray['sign-date-certificate-provider'],
                    'attorneys' => $attorneySignatureDates,
                ]);
            }
        }
                
        $viewParams['form'] = $form;
        
        return new ViewModel($viewParams);
    }
}
