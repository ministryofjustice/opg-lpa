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

class DownloadController extends AbstractLpaController
{
    public function indexAction()
    {
        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');
        
        $this->log()->info('PDF type is ' . $pdfType, ['lpaId' => $this->getLpa()->id]);
        
        // check PDF availability. return a nice error if unavailable.
        if((($pdfType == 'lpa120') && !$this->getFlowChecker()->canGenerateLPA120())
                || (($pdfType == 'lp3') && !$this->getFlowChecker()->canGenerateLP3())
                || (($pdfType == 'lpa1') && !$this->getFlowChecker()->canGenerateLP1())) {
            
            $this->log()->info('PDF not available', ['lpaId' => $this->getLpa()->id]);
                    
            return new ViewModel();
        }
        
        $this->layout('layout/download.twig');

        $details = $this->getLpaApplicationService()->getPdfDetails($this->getLpa()->id, $pdfType);
        
        $this->log()->info('PDF status is ' . $details['status'], ['lpaId' => $this->getLpa()->id]);
        
        if ($details['status'] == 'in-queue') {
            return false;
        }
        else {
            
            $this->log()->info('Delivering PDF', ['lpaId' => $this->getLpa()->id]);
            
            header('Content-disposition: inline; filename="Lasting-Power-of-Attorney-' . ucfirst($pdfType) . '.pdf"');
            header('Content-Type: application/pdf');
            
            // These two headers are critically important for working around an IE7/8 bug regarding downloading files over SSL
            header('Cache-control: private');
            header('Pragma: public');
            
            echo $this->getLpaApplicationService()->getPdf($this->getLpa()->id, $pdfType);
        }
        
        return $this->getResponse();
    }
}
