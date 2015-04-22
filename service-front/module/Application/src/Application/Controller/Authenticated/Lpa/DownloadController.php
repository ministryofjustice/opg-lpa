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

class DownloadController extends AbstractLpaController
{
    public function indexAction()
    {
        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');
        $this->layout('layout/download.phtml');

        $details = $this->getLpaApplicationService()->getPdfDetails($this->getLpa()->id, $pdfType);
        if ($details['status'] == 'in-queue') {
            return false;
        }
        else {
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
