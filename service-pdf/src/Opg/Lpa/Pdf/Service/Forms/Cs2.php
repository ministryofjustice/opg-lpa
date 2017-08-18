<?php

namespace Opg\Lpa\Pdf\Service\Forms;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Config\Config;
use mikehaertl\pdftk\Pdf;

class Cs2 extends AbstractForm
{
    private $contentType;
    private $content;

    const BOX_NO_OF_ROWS_CS2 = 14;

    /**
     * @param Lpa $lpa
     * @param enum $contentType - CONTENT_TYPE_ATTORNEY_DECISIONS | CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN | CONTENT_TYPE_PREFERENCES | CONTENT_TYPE_INSTRUCTIONS
     * @param string $content
     */
    public function __construct(Lpa $lpa, $contentType, $content)
    {
        parent::__construct($lpa);

        $this->contentType = $contentType;
        $this->content = $content;
    }

    /**
     * (non-PHPdoc)
     * @see \Opg\Lpa\Pdf\Service\Forms\AbstractForm::generate()
     */
    public function generate()
    {
        $this->logGenerationStatement();

        $cs2Continued = '';
        $formatedContentLength = strlen($this->flattenTextContent($this->content));
        if (($this->contentType == self::CONTENT_TYPE_ATTORNEY_DECISIONS) || ($this->contentType == self::CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN)) {
            $totalAdditionalPages = ceil($formatedContentLength / ((Lp1::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS_CS2));
        } else {
            $contentLengthOnStandardForm = (Lp1::BOX_CHARS_PER_ROW + 2) * Lp1::BOX_NO_OF_ROWS;
            $totalAdditionalPages = ceil(($formatedContentLength - $contentLengthOnStandardForm) / ((Lp1::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS_CS2));
        }

        for ($i = 0; $i < $totalAdditionalPages; $i++) {
            $filePath = $this->registerTempFile('CS2');

            if (($this->contentType == self::CONTENT_TYPE_ATTORNEY_DECISIONS) || ($this->contentType == self::CONTENT_TYPE_REPLACEMENT_ATTORNEY_STEP_IN)) {
                $pageNo = $i;
            } else {
                $pageNo = $i + 1;
            }

            if (($i > 0) ||
                ($this->contentType == self::CONTENT_TYPE_PREFERENCES) ||
                ($this->contentType == self::CONTENT_TYPE_INSTRUCTIONS)) {
                $cs2Continued = '(Continued)';
            }

            //  Set the PDF form data
            $this->pdfFormData['cs2-is'] = $this->contentType;
            $this->pdfFormData['cs2-content'] = $this->getContentForBox($pageNo, $this->content, $this->contentType);
            $this->pdfFormData['cs2-donor-full-name'] = $this->fullName($this->lpa->document->donor->name);
            $this->pdfFormData['cs2-continued'] = $cs2Continued;
            $this->pdfFormData['cs2-footer-right'] = Config::getInstance()['footer']['cs2'];

            $this->pdf = new Pdf($this->pdfTemplatePath . "/LPC_Continuation_Sheet_2.pdf");

            $this->pdf->fillForm($this->pdfFormData)
                      ->flatten()
                      ->saveAs($filePath);
        }

        return $this->interFileStack;
    }
}
