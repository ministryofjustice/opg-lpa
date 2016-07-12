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
    protected $contentHeader = 'blank-header-partial.phtml';
    
    public function indexAction()
    {
        $lpa = $this->getLpa();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();
        
        $form = $this->getServiceLocator()->get('FormElementManager')->get('Application\Form\Lpa\DateCheckForm', [
            'lpa'=>$lpa,
        ]);
        
        $fromPage = $this->params()->fromRoute('from-page');
        
        if ($fromPage == 'complete') {
            $returnRoute = $this->url()->fromRoute('lpa/complete', ['lpa-id' => $lpa->get('id')]);
        } else {
            $returnRoute = $this->url()->fromRoute('user/dashboard');
        }
        
        if($this->request->isPost()) {
            
            $post = $this->request->getPost();
            $returnRoute = $post['returnRoute'];
            
            $form->setData($post);
            
            $postArray = $post->toArray();

            if($form->isValid()) {
                $attorneySignatureDates = [];
                foreach($postArray as $name => $date) {
                    if(preg_match('/sign-date-(attorney|replacement-attorney)-\d/', $name)) {
                        $attorneySignatureDates[] = $date;
                    }
                }
                
                $result = DateCheck::checkDates([
                    'donor' => $postArray['sign-date-donor'],
                    'donor-life-sustaining' => $postArray['sign-date-donor-life-sustaining'] ?: null,
                    'certificate-provider' => $postArray['sign-date-certificate-provider'],
                    'attorneys' => $attorneySignatureDates,
                ]);
                
                if ($result === true) {
                    $viewParams['valid'] = true;
                } else {
                    $viewParams['dateError'] = $result;
                }
            }
        }
                
        $viewParams['form'] = $form;
        $viewParams['returnRoute'] = $returnRoute;
        
        return new ViewModel($viewParams);
    }
}
