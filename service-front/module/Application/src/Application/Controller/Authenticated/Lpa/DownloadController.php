<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Zend\View\Model\ViewModel;

class DownloadController extends AbstractLpaController
{
    public function indexAction()
    {
        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');

        $this->getLogger()->info('PDF type is ' . $pdfType, [
            'lpaId' => $this->getLpa()->id
        ]);

        // check PDF availability. return a nice error if unavailable.
        if (($pdfType == 'lpa120' && !$this->getFlowChecker()->canGenerateLPA120())
            || ($pdfType == 'lp3' && !$this->getFlowChecker()->canGenerateLP3())
            || ($pdfType == 'lp1' && !$this->getFlowChecker()->canGenerateLP1())) {

            $this->getLogger()->info('PDF not available', [
                'lpaId' => $this->getLpa()->id
            ]);

            //  Just redirect to the index template - that contains the error message to display
            return new ViewModel();
        }

        $this->layout('layout/download.twig');

        $details = $this->getLpaApplicationService()
                        ->getPdfDetails($this->getLpa()->id, $pdfType);

        $this->getLogger()->info('PDF status is ' . $details['status'], [
            'lpaId' => $this->getLpa()->id
        ]);

        if ($details['status'] === 'ready') {
            //  Redirect to download action
            return $this->redirect()->toRoute('lpa/download/file', [
                'lpa-id'       => $this->getLpa()->id,
                'pdf-type'     => $pdfType,
                'pdf-filename' => $this->getFilename($pdfType),
            ]);
        }

        return false;
    }

    public function downloadAction()
    {
        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');

        $details = $this->getLpaApplicationService()->getPdfDetails($this->getLpa()->id, $pdfType);

        if ($details['status'] !== 'ready') {
            // If the PDF is not ready, direct the user back to index.
            return $this->redirect()->toRoute('lpa/download', [
                'lpa-id'   => $this->getLpa()->id,
                'pdf-type' => $pdfType
            ]);
        }

        $fileContents = $this->getLpaApplicationService()->getPdf($this->getLpa()->id, $pdfType);

        $response = $this->getResponse();
        $response->setContent($fileContents);

        $headers = $response->getHeaders();
        $headers->clearHeaders()
                ->addHeaderLine('Content-Type', 'application/pdf')
                ->addHeaderLine('Content-Disposition', 'attachment; filename="' . $this->getFilename($pdfType) .'"')
                ->addHeaderLine('Content-Transfer-Encoding', 'Binary')
                ->addHeaderLine('Content-Description', 'File Transfer')
                ->addHeaderLine('Pragma', 'public')
                ->addHeaderLine('Expires', '0')
                ->addHeaderLine('Cache-Control', 'must-revalidate')
                ->addHeaderLine('Content-Length', strlen($fileContents));

        return $this->response;
    }

    /**
     * Get the filename to use for this PDF type
     *
     * @param $pdfType
     * @return string
     */
    private function getFilename($pdfType)
    {
        $lpaTypeChar = '';

        //  If this is an LP1 document then append a type char to the end of the filename
        if ($pdfType == 'lp1') {
            $lpaTypeChar = ($this->getLpa()->document->type == Document::LPA_TYPE_PF ? 'F' : 'H');
        }

        return 'Lasting-Power-of-Attorney-' . strtoupper($pdfType) . $lpaTypeChar . '.pdf';
    }
}