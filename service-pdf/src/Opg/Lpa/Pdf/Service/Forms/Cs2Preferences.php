<?php

namespace Opg\Lpa\Pdf\Service\Forms;

class Cs2Preferences extends AbstractCs2
{
    /**
     * (non-PHPdoc)
     * @see \Opg\Lpa\Pdf\Service\Forms\AbstractForm::generate()
     */
    public function generate()
    {
        $this->logGenerationStatement();

        $content = $this->lpa->document->preference;

        $formattedContentLength = strlen($this->flattenTextContent($content));
        $contentLengthOnStandardForm = (self::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS;
        $totalAdditionalPages = ceil(($formattedContentLength - $contentLengthOnStandardForm) / ((self::BOX_CHARS_PER_ROW + 2) * self::BOX_NO_OF_ROWS_CS2));

        for ($i = 0; $i < $totalAdditionalPages; $i++) {
            $filePath = $this->registerTempFile('CS2');

            //  Set the PDF form data
            $this->dataForForm['cs2-is'] = self::CONTENT_TYPE_PREFERENCES;
            $this->dataForForm['cs2-content'] = $this->getInstructionsAndPreferencesContent($i + 1, $content);
            $this->dataForForm['cs2-donor-full-name'] = $this->lpa->document->donor->name->__toString();
            $this->dataForForm['cs2-continued'] = '(Continued)';
            $this->dataForForm['cs2-footer-right'] = $this->config['footer']['cs2'];

            $pdf = $this->getPdfObject(true);
            $pdf->fillForm($this->dataForForm)
                ->flatten()
                ->saveAs($filePath);
        }

        return $this->interFileStack;
    }
}
