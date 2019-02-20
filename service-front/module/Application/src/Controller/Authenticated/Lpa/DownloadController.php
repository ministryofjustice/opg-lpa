<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Application\Model\Service\Analytics\GoogleAnalyticsService;
use Exception;
use Opg\Lpa\DataModel\Lpa\Document\Document;
use Zend\View\Model\ViewModel;

class DownloadController extends AbstractLpaController
{
    /**
     * @var GoogleAnalyticsService
     */
    private $analyticsService;

    public function indexAction()
    {
        $lpa = $this->getLpa();

        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');

        $this->getLogger()->info('PDF type is ' . $pdfType, [
            'lpaId' => $lpa->getId()
        ]);

        // check PDF availability. return a nice error if unavailable.
        if (($pdfType == 'lpa120' && !$lpa->canGenerateLPA120())
            || ($pdfType == 'lp3' && !$lpa->canGenerateLP3())
            || ($pdfType == 'lp1' && !$lpa->canGenerateLP1())) {
            $this->getLogger()->info('PDF not available', [
                'lpaId' => $lpa->getId()
            ]);

            //  Just redirect to the index template - that contains the error message to display
            return new ViewModel();
        }

        $this->layout('layout/download.twig');

        if ($this->pdfIsReady($lpa->getId(), $pdfType)) {
            //  Redirect to download action
            return $this->redirect()->toRoute('lpa/download/check', [
                'lpa-id'       => $lpa->getId(),
                'pdf-type'     => $pdfType,
                'pdf-filename' => $this->getFilename($pdfType)
            ]);
        }

        return false;
    }

    public function checkAction()
    {
        $lpa = $this->getLpa();

        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');

        if (!$this->pdfIsReady($lpa->getId(), $pdfType)) {
            // If the PDF is not ready, direct the user back to index.
            return $this->redirect()->toRoute('lpa/download', [
                'lpa-id'   => $lpa->getId(),
                'pdf-type' => $pdfType
            ]);
        }

        $model = new ViewModel(['data' =>
                ['lpaid'       => $lpa->getId(),
                'pdftype'     => $pdfType,
                'pdffilename' => $this->getFilename($pdfType)
            ]]);
//        $model->setVariables([
//            'lpa-id'       => 12344,
//            'pdf-type'     => $pdfType,
//            'pdf-filename' => $this->getFilename($pdfType)
//        ]);
        $model->setTemplate('layout/downloading.twig');

        return $model;

    }

    public function downloadAction()
    {
        $lpa = $this->getLpa();

        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');

        if (!$this->pdfIsReady($lpa->getId(), $pdfType)) {
            // If the PDF is not ready, direct the user back to index.
            return $this->redirect()->toRoute('lpa/download', [
                'lpa-id'   => $lpa->getId(),
                'pdf-type' => $pdfType
            ]);
        }

        //  Get the file contents by requesting the PDF again but with the .pdf file extension
        $fileContents = $this->getLpaApplicationService()->getPdfContents($lpa->getId(), $pdfType);

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

        $fileName = $this->getFilename($pdfType);

        $userAgent = $this->getRequest()->getHeaders()->get('User-Agent')->getFieldValue();
        if (stripos($userAgent, 'edge/') !== false) {
            //Microsoft edge. Send the file as an attachment
            $headers->addHeaderLine('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } else {
            $headers->addHeaderLine('Content-Disposition', 'inline; filename="' . $fileName . '"');
        }

        // Send a page view to the analytics service for the document being provided
        try {
            $uri = $this->getRequest()->getUri();
            $this->analyticsService->sendPageView($uri->getHost(), $uri->getPath(), $fileName);
        } catch (Exception $ex) {
            // Log the error but don't impact the user because of analytics failures
            $this->getLogger()->err($ex);
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
     * @param string $pdfType
     * @return string
     */
    private function getFilename(string $pdfType) : string
    {
        $lpa = $this->getLpa();

        $lpaTypeChar = '';

        //  If this is an LP1 document then append a type char to the end of the filename
        if ($pdfType == 'lp1') {
            $lpaTypeChar = ($lpa->document->type == Document::LPA_TYPE_PF ? 'F' : 'H');
        }

        $draftString = '';

        if (!$lpa->isStateCompleted()) {
            $draftString = 'Draft-';
        }

        return $draftString . 'Lasting-Power-of-Attorney-' . strtoupper($pdfType) . $lpaTypeChar . '.pdf';
    }

    /**
     * Set the service to be used for sending analytics data
     *
     * @param GoogleAnalyticsService
     */
    public function setAnalyticsService($analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }
}
