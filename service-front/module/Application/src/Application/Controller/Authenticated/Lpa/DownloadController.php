<?php

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

        if ( $details['status'] !== 'ready' ){
            return false;
        }
        else {

            // Redirect to download action.
            return $this->redirect()->toRoute('lpa/download/file', [
                'lpa-id'=>$this->getLpa()->id,
                'pdf-type'=>$pdfType,
                'pdf-filename'=>'Lasting-Power-of-Attorney-' . ucfirst($pdfType) . '.pdf'
            ]);

        }

        return $this->getResponse();
    }

    public function downloadAction(){

        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');

        $details = $this->getLpaApplicationService()->getPdfDetails($this->getLpa()->id, $pdfType);

        if( $details['status'] !== 'ready' ){

            // If the PDF is not ready, direct the user back to index.
            return $this->redirect()->toRoute('lpa/download', ['lpa-id'=>$this->getLpa()->id, 'pdf-type'=>$pdfType]);

        }

        //---

        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');

        $fileContents = $this->getLpaApplicationService()->getPdf($this->getLpa()->id, $pdfType);

        //---

        $response = $this->getResponse();
        $response->setContent($fileContents);

        $headers = $response->getHeaders();
        $headers->clearHeaders()
            ->addHeaderLine('Content-Type', 'application/pdf')
            ->addHeaderLine('Content-Disposition', 'inline; filename="Lasting-Power-of-Attorney-' . ucfirst($pdfType) . '.pdf"')
            ->addHeaderLine('Content-Length', strlen($fileContents));


        return $this->response;

    }

}
