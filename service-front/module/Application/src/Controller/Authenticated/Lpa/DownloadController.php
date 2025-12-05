<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Laminas\Http\Response as HttpResponse;
use Laminas\View\Model\ViewModel;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\Logging\LoggerTrait;

class DownloadController extends AbstractLpaController
{
    use LoggerTrait;

    /**
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @return ViewModel|HttpResponse|false
     */
    public function indexAction()
    {
        $lpa = $this->getLpa();

        $pdfType = $this->getEvent()->getRouteMatch()->getParam('pdf-type');

        $this->getLogger()->debug('PDF type is ' . $pdfType, [
            'lpaId' => $lpa->getId(),
            'pdfType' => $pdfType
        ]);
        // check PDF availability. return a nice error if unavailable
        if (
            ($pdfType == 'lpa120' && !$lpa->canGenerateLPA120())
            || ($pdfType == 'lp3' && !$lpa->canGenerateLP3())
            || ($pdfType == 'lp1' && !$lpa->canGenerateLP1())
        ) {
            $this->getLogger()->warning('PDF not available', [
                'lpaId' => $lpa->getId(),
                'pdfType' => $pdfType
            ]);

            return $this->notFoundAction();
        }

        $this->layout('layout/download.twig');

        if ($this->pdfIsReady($lpa->getId(), $pdfType)) {
            // Redirect to download action
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

        /** @var HttpResponse */
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
                ->addHeaderLine('Content-Length', '' . strlen($fileContents));

        $fileName = $this->getFilename($pdfType);

        $request = $this->convertRequest();

        $userAgent = $request->getHeaders('User-Agent')->getFieldValue();
        if (stripos($userAgent, 'edge/') !== false) {
            //Microsoft edge. Send the file as an attachment
            $headers->addHeaderLine('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } else {
            $headers->addHeaderLine('Content-Disposition', 'inline; filename="' . $fileName . '"');
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
        $result = $this->getLpaApplicationService()
                    ->getPdf($lpaId, $pdfType);

        $this->getLogger()->debug('PDF status is ' . $result['status'], [
            'lpaId' => $lpaId,
            'pdfType' => $pdfType
        ]);

        if (!is_array($result)) {
            return $result;
        }

        return ($result['status'] === 'ready');
    }

    /**
     * Get the filename to use for this PDF type
     *
     * @param string $pdfType
     * @return string
     */
    private function getFilename(string $pdfType): string
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
}
