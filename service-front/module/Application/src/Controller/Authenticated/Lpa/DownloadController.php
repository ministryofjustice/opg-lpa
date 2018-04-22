<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Zend\View\Model\ViewModel;

class DownloadController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');

        $this->getLogger()->info('PDF type is ' . $pdfType, [
            'lpaId' => $lpa->id
        ]);

        // check PDF availability. return a nice error if unavailable.
        if (($pdfType == 'lpa120' && !$this->getFlowChecker()->canGenerateLPA120())
            || ($pdfType == 'lp3' && !$this->getFlowChecker()->canGenerateLP3())
            || ($pdfType == 'lp1' && !$this->getFlowChecker()->canGenerateLP1())) {

            $this->getLogger()->info('PDF not available', [
                'lpaId' => $lpa->id
            ]);

            //  Just redirect to the index template - that contains the error message to display
            return new ViewModel();
        }

        $this->layout('layout/download.twig');

        if ($this->pdfIsReady($lpa->id, $pdfType)) {
            //  Redirect to download action
            return $this->redirect()->toRoute('lpa/download/file', [
                'lpa-id'       => $lpa->id,
                'pdf-type'     => $pdfType,
                'pdf-filename' => $this->getFilename($pdfType),
            ]);
        }

        return false;
    }

    public function downloadAction()
    {
        $lpa = $this->getLpa();

        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');

        if (!$this->pdfIsReady($lpa->id, $pdfType)) {
            // If the PDF is not ready, direct the user back to index.
            return $this->redirect()->toRoute('lpa/download', [
                'lpa-id'   => $lpa->id,
                'pdf-type' => $pdfType
            ]);
        }

        //  Get the file contents by requesting the PDF again but with the .pdf file extension
        $fileContents = $this->getLpaApplicationService()->getPdfContents($lpa->id, $pdfType);

        $response = $this->getResponse();
        $response->setContent($fileContents);

        $headers = $response->getHeaders();
        $headers->clearHeaders()
                ->addHeaderLine('Content-Type', 'application/pdf')
                ->addHeaderLine('Content-Transfer-Encoding', 'Binary')
                ->addHeaderLine('Content-Description', 'File Transfer')
                ->addHeaderLine('Pragma', 'public')
                ->addHeaderLine('Expires', '0')
                ->addHeaderLine('Cache-Control', 'must-revalidate')
                ->addHeaderLine('Content-Length', strlen($fileContents));

        $userAgent = $this->getRequest()->getHeaders()->get('User-Agent')->getFieldValue();
        if (stripos($userAgent, 'edge/') !== false) {
            //Microsoft edge. Send the file as an attachment
            $headers->addHeaderLine('Content-Disposition', 'attachment; filename="' . $this->getFilename($pdfType) .'"');
        } else {
            $headers->addHeaderLine('Content-Disposition', 'inline; filename="' . $this->getFilename($pdfType) .'"');
        }

        return $this->response;
    }

    /**
     * Check to see if the PDF is ready to retrieve
     *
     * @param $lpaId
     * @param $pdfType
     * @return bool
     */
    private function pdfIsReady($lpaId, $pdfType)
    {
        $details = $this->getLpaApplicationService()
                        ->getPdf($lpaId, $pdfType);

        $this->getLogger()->info('PDF status is ' . $details['status'], [
            'lpaId' => $lpaId,
        ]);

        return ($details['status'] === 'ready');
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